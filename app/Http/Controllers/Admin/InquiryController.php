<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquiry;
use App\Models\Patron;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmed;
use App\Services\AiReplyService;
use App\Services\AvailabilityService;
use App\Services\VenueOccupancyService;
use App\Support\BookingRules;
use App\Support\VenueSlot;


class InquiryController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::with('patron')->latest()->paginate(10);
        return view('admin.inquiry', compact('inquiries'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Pending,In Progress,Completed,Cancelled',
        ]);

        $inquiry = Inquiry::with('patron')->find($id);
        $inquiry->status = $request->input('status');
        $inquiry->save();

        return redirect()->route('admin.inquiry')->with('success', 'Inquiry status updated.');
    }

    public function updateStatusAjax(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Pending,In Progress,Completed,Cancelled',
            // Optional: only needed when the inquiry carries no period and the
            // admin has to choose one before the slot can be reserved.
            'period' => 'sometimes|nullable|string|max:4',
        ]);

        $inquiry = Inquiry::with('patron')->find($id);
        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        $newStatus = $request->input('status');
        $mailSent = true;

        // Marking an inquiry Completed converts it to a reservation. The venue
        // slot is checked and reserved BEFORE the status is stored, so a
        // rejected conversion leaves the inquiry as it was and the admin UI can
        // safely revert the dropdown.
        if ($newStatus === 'Completed') {
            $existing = \App\Models\Reservation::where('inquiry_id', $inquiry->inquiry_id)->first();

            if (!$existing) {
                $conversion = $this->convertToReservation($inquiry, $request->input('period'));

                if ($conversion['response'] !== null) {
                    return $conversion['response'];
                }

                $mailSent = $conversion['mail_sent'];
            }
        }

        $inquiry->status = $newStatus;
        $inquiry->save();

        return response()->json(['success' => true, 'mail_sent' => $mailSent]);
    }

    /**
     * Convert an inquiry into an active reservation.
     *
     * The venue + date + period slot is locked, rechecked inside a transaction,
     * and only then written, so two simultaneous conversions for the same slot
     * create exactly one reservation. A conflict returns 409 and writes nothing.
     *
     * @return array{response: \Illuminate\Http\JsonResponse|null, mail_sent: bool}
     */
    private function convertToReservation(Inquiry $inquiry, ?string $requestedPeriod): array
    {
        // Guarded with the raw inquiry value on purpose. The "Others" option is
        // free text, so it stays exempt from the conflict check; the row itself
        // records the text the patron typed, see reservationVenue().
        $venue = (string) ($inquiry->venue ?? '');
        $date = $inquiry->date ? $inquiry->date->format('Y-m-d') : null;

        $period = VenueSlot::normalizePeriod($requestedPeriod)
            ?? VenueSlot::periodFromFormData($inquiry->form_data)
            ?? VenueSlot::periodFromTimeText($inquiry->time);

        // A bookable venue with no determinable period cannot be locked, so ask
        // the admin for one instead of guessing.
        if ($date !== null && VenueSlot::isTrackable($venue) && $period === null) {
            return [
                'response' => response()->json([
                    'success' => false,
                    'needs_period' => true,
                    'message' => VenueSlot::PERIOD_REQUIRED_MESSAGE,
                ], 422),
                'mail_sent' => true,
            ];
        }

        Log::info('Attempting to create reservation from inquiry:', $inquiry->toArray());

        $mailSent = true;

        try {
            $outcome = app(VenueOccupancyService::class)->guard(
                $venue,
                (string) $date,
                (string) $period,
                function () use ($inquiry, &$mailSent) {
                    $reservation = $this->createReservationRow($inquiry);
                    $mailSent = $this->sendConfirmationEmail($inquiry, $reservation);
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create reservation: ' . $e->getMessage());

            return [
                'response' => response()->json([
                    'success' => false,
                    'message' => 'Reservation creation failed. Please try again.',
                ], 500),
                'mail_sent' => true,
            ];
        }

        if ($outcome === VenueOccupancyService::RESULT_CONFLICT) {
            return [
                'response' => response()->json([
                    'success' => false,
                    'message' => VenueSlot::CONFLICT_MESSAGE,
                ], 409),
                'mail_sent' => true,
            ];
        }

        if ($outcome === VenueOccupancyService::RESULT_BUSY) {
            return [
                'response' => response()->json([
                    'success' => false,
                    'message' => VenueSlot::TRY_AGAIN_MESSAGE,
                ], 503),
                'mail_sent' => true,
            ];
        }

        return ['response' => null, 'mail_sent' => $mailSent];
    }

    private function createReservationRow(Inquiry $inquiry): \App\Models\Reservation
    {
        return \App\Models\Reservation::create([
            'inquiry_id'         => $inquiry->inquiry_id,
            'patron_id'          => $inquiry->patron_id,
            'date'               => $inquiry->date,
            'time'               => $inquiry->time,
            'venue'              => $this->reservationVenue($inquiry),
            'event_type'         => $inquiry->event_type,
            'theme_motif'        => $inquiry->theme_motif,
            'message'            => $inquiry->message,
            'status'             => 'active',
            'form_data'          => $inquiry->form_data, // carry forward dynamic data
        ]);
    }

    /**
     * Venue recorded on the reservation.
     *
     * When the inquiry picked the free-text "Others" option, the text the patron
     * typed is copied across so the reservation no longer just says "Others".
     * New conversions only; existing rows are left alone.
     */
    private function reservationVenue(Inquiry $inquiry): ?string
    {
        $venue = $inquiry->venue;

        if (VenueSlot::normalize($venue) === 'others') {
            return $inquiry->other_venue ?: $venue;
        }

        return $venue;
    }

    /**
     * Returns false when the mail could not be handed to the mailer. The
     * reservation is already committed at this point, so the caller reports the
     * partial success instead of failing the whole conversion.
     */
    private function sendConfirmationEmail(Inquiry $inquiry, \App\Models\Reservation $reservation): bool
    {
        if (!$inquiry->relationLoaded('patron') || !$inquiry->patron) {
            return true;
        }

        $emailData = [
            'name'              => $inquiry->patron->name,
            'tracking_code'     => $inquiry->tracking_code,
            'date'              => $inquiry->date ? $inquiry->date->format('Y-m-d') : 'N/A',
            'time'              => $inquiry->time ?? 'N/A',
            'venue'             => $inquiry->venue ?? 'N/A',
            'other_venue'       => $inquiry->other_venue ?? '',
            'event_type'        => $inquiry->event_type ?? 'N/A',
            'other_event_type'  => $inquiry->other_event_type ?? '',
            'theme_motif'       => $inquiry->theme_motif ?? 'N/A',
            'other_theme_motif' => $inquiry->other_theme_motif ?? '',
            'message'           => $inquiry->message ?? '',
        ];

        try {
            Mail::to($inquiry->patron->email)->send(new ReservationConfirmed($emailData));
            Log::info('Confirmation email sent to ' . $inquiry->patron->email . ' for inquiry #' . $inquiry->inquiry_id);

            return true;
        } catch (\Exception $mailError) {
            Log::error('Failed to send confirmation email for inquiry #' . $inquiry->inquiry_id . ': ' . $mailError->getMessage());

            return false;
        }
    }

    /**
     * Generate an AI-assisted reply draft for an inquiry (Google Gemini).
     */
    public function draftReply(Request $request)
    {
        $request->validate([
            'inquiry_id' => 'required|exists:inquiry,inquiry_id',
        ]);

        $inquiry = Inquiry::with('patron')->find($request->inquiry_id);

        if (! $inquiry) {
            return response()->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
        }

        $context = [
            'patron_name' => $inquiry->patron->name ?? 'Valued Customer',
            'event_type'  => $inquiry->event_type === 'Others' ? ($inquiry->other_event_type ?? '') : ($inquiry->event_type ?? ''),
            'venue'       => $inquiry->venue === 'Others' ? ($inquiry->other_venue ?? '') : ($inquiry->venue ?? ''),
            'theme_motif' => $inquiry->theme_motif === 'Others' ? ($inquiry->other_theme_motif ?? '') : ($inquiry->theme_motif ?? ''),
            'date'        => $inquiry->date ? $inquiry->date->format('F j, Y') : '',
            'time'        => $inquiry->time ?? '',
            'status'      => $inquiry->status ?? 'Pending',
            'message'     => $inquiry->message ?? '',
        ];

        try {
            $draft = AiReplyService::generateDraft($context);

            return response()->json(['success' => true, 'draft' => $draft]);
        } catch (\Throwable $e) {
            // Generic on purpose: a provider error can echo the prompt, which
            // would put patron data in the log and in the response.
            Log::error('AI draft reply failed', ['type' => $e::class]);

            return response()->json([
                'success' => false,
                'message' => 'The AI service is unavailable right now. Please try again.',
            ], 500);
        }
    }


    public function store(Request $request)
    {
        // Validate all fields
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'contact_number' => 'required|string|max:30',
            'date'           => 'required|date',
            'time'           => 'required|string',
            'venue'          => 'required|string|max:255',
            'event_type'     => 'required|string|max:255',
            'theme_motif'    => 'nullable|string|max:255',
            'message'        => 'nullable|string',
        ]);

        try {
            // A closed date is refused for everyone, admins included. Past dates
            // are allowed here: an admin recording an event that already
            // happened must not be blocked.
            if (app(AvailabilityService::class)->isClosed($validated['date'])) {
                return $this->rejectInquiry($request, BookingRules::CLOSED_MESSAGE);
            }

            // Same courtesy occupancy check as the Make Reservation form. An
            // inquiry does not occupy a venue, but a venue that is already
            // booked is refused early with the same message.
            $period = VenueSlot::normalizePeriod($request->input('period'))
                ?? VenueSlot::periodFromTimeText($validated['time']);

            if (VenueSlot::isTrackable($validated['venue']) && $period !== null
                && app(VenueOccupancyService::class)->isOccupied($validated['venue'], $validated['date'], $period)) {
                return $this->rejectInquiry($request, VenueSlot::CONFLICT_MESSAGE);
            }

            // Create or update the patron
            $patron = Patron::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'name'           => $validated['name'],
                    'contact_number' => $validated['contact_number'],
                ]
            );

            if (!$patron || !$patron->patron_id) {
                return redirect()->back()->with('error', 'Failed to create or find patron.');
            }

            // Handle "Others" values
            $isEventOther = strtolower(trim($validated['event_type'])) === 'others';
            $isThemeOther = strtolower(trim($validated['theme_motif'])) === 'others';
            $isVenueOther = strtolower(trim($validated['venue'])) === 'others';

            // Create the inquiry
            Inquiry::create([
                'patron_id'         => $patron->patron_id, // make sure this is correct
                'admin_id'          => auth('admin')->id(),
                'created_by_type'   => 'admin',
                'date'              => $validated['date'],
                'time'              => $validated['time'],
                'venue'             => $isVenueOther ? 'Others' : $validated['venue'],
                'event_type'        => $isEventOther ? 'Others' : $validated['event_type'],
                'theme_motif'       => $isThemeOther ? 'Others' : $validated['theme_motif'],
                'other_event_type'  => $isEventOther ? $request->input('other_event_type') : null,
                'other_theme_motif' => $isThemeOther ? $request->input('other_theme_motif') : null,
                'other_venue'       => $isVenueOther ? $request->input('other_venue') : null,
                'message'           => $validated['message'],
                'status'            => 'Pending',
            ]);

            return redirect()->back()->with('success', 'Inquiry successfully created!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to store inquiry. Error: ' . $e->getMessage());
        }
    }

    /**
     * Rejects an inquiry without writing a row. AJAX callers get HTTP 409 with
     * the message; form posts return to the form with the input preserved.
     */
    private function rejectInquiry(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 409);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
