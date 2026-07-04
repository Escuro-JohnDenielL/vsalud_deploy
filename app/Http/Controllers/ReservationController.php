<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patron;
use App\Models\Inquiry;
use App\Models\GuestConsent;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationSubmitted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function create(Request $request)
    {
        if ($request->is('admin/*')) {
            return view('admin.reserve');
        }

        $agreementAccepted = (bool) $request->session()->get('guest_agreed', false);
        $guestToken = $request->cookie('guest_token');
        $consent = null;

        if (!$agreementAccepted && $guestToken) {
            $consent = GuestConsent::query()
                ->where('guest_token', $guestToken)
                ->where('expires_at', '>', now())
                ->first();
        }

        if (!$agreementAccepted && !$consent) {
            $consent = GuestConsent::query()
                ->where('ip_address', $request->ip())
                ->where('expires_at', '>', now())
                ->latest('consented_at')
                ->first();
        }

        if ($consent) {
            $agreementAccepted = true;
            $guestToken = $consent->guest_token;
            $request->session()->put('guest_agreed', true);
            $request->session()->put('guest_token', $guestToken);
        }

        $response = response()->view('patron.mreserve', compact('agreementAccepted'));

        if ($guestToken) {
            $response->cookie('guest_token', $guestToken, 60 * 24 * 30);
        }

        return $response;
    }

    public function fetch_vreserve(Request $request)
    {
        return view('patron.vreserve');
    }

    public function store(Request $request)
    {
        Log::info('Reservation form submission', $request->all());

        // Load the published reservation form definition
        $form = Form::with('activeFields')->where('slug', 'reservation')->where('is_published', true)->first();

        if (!$form) {
            return redirect()->back()->with('error', 'The reservation form is currently unavailable.');
        }

        // Build dynamic validation rules from the form fields
        $rules = [];
        foreach ($form->activeFields as $field) {
            $fieldRules = [];

            if ($field->required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Add type-specific rules
            $fieldRules = array_merge($fieldRules, match ($field->field_type) {
                'email' => ['email', 'max:255'],
                'tel'   => ['string', 'max:20'],
                'textarea' => ['string'],
                'select', 'radio' => ['string'],
                'checkbox' => ['array'],
                'date'  => ['date'],
                'time'  => [],
                default => ['string', 'max:255'], // text
            });

            if ($field->field_type === 'email') {
                $fieldRules[] = 'max:255';
            }

            $rules[$field->name] = $fieldRules;

            // If field has "Others" option, the _other field is only required when value is "Others"
            if ($field->has_other_option) {
                $otherName = $field->name . '_other';
                $rules[$otherName] = 'nullable|string|max:255';
            }
        }

        $validated = $request->validate($rules);

        // Extract patron fields (always present in the form)
        $patron = Patron::firstOrCreate(
            ['email' => $validated['email'] ?? $request->input('email')],
            [
                'name'           => $validated['name'] ?? $request->input('name', ''),
                'contact_number' => $validated['contact_number'] ?? $request->input('contact_number', ''),
            ]
        );

        // Build form_data JSON — all validated field values
        $formData = [];
        $fixedFields = ['name', 'email', 'contact_number', 'date', 'time', 'venue', 'event_type', 'theme_motif', 'message'];
        $hardcodedData = [];

        foreach ($form->activeFields as $field) {
            $fieldName = $field->name;
            $fieldValue = $validated[$fieldName] ?? $request->input($fieldName);

            // Handle "Others" fields
            if ($field->has_other_option && $fieldValue === 'Others') {
                $otherValue = $validated[$fieldName . '_other'] ?? $request->input($fieldName . '_other');
                $formData[$fieldName] = 'Others';
                if ($otherValue) {
                    $formData[$fieldName . '_other'] = $otherValue;
                }
                // For backward compat, also store in the legacy other columns
                if (in_array($fieldName, ['venue', 'event_type', 'theme_motif'])) {
                    $hardcodedData[$fieldName] = 'Others';
                    $hardcodedData['other_' . $fieldName] = $otherValue;
                }
            } else {
                $formData[$fieldName] = $fieldValue;
                if (in_array($fieldName, $fixedFields)) {
                    $hardcodedData[$fieldName] = $fieldValue;
                }
            }
        }

        try {
            $inquiry = Inquiry::create([
                'patron_id'         => $patron->patron_id,
                'event_type'        => $hardcodedData['event_type'] ?? $validated['event_type'] ?? null,
                'other_event_type'  => $hardcodedData['other_event_type'] ?? null,
                'theme_motif'       => $hardcodedData['theme_motif'] ?? $validated['theme_motif'] ?? null,
                'other_theme_motif' => $hardcodedData['other_theme_motif'] ?? null,
                'venue'             => $hardcodedData['venue'] ?? $validated['venue'] ?? null,
                'other_venue'       => $hardcodedData['other_venue'] ?? null,
                'date'              => $hardcodedData['date'] ?? $validated['date'] ?? null,
                'time'              => $hardcodedData['time'] ?? $validated['time'] ?? null,
                'message'           => $hardcodedData['message'] ?? $validated['message'] ?? null,
                'status'            => 'Pending',
                'form_data'         => $formData, // Store all dynamic field data as JSON
            ]);

            // Send email with available data
            $emailData = [
                'name'         => $patron->name,
                'tracking_code' => $inquiry->tracking_code,
                'date'         => $hardcodedData['date'] ?? $formData['date'] ?? 'N/A',
                'time'         => $hardcodedData['time'] ?? $formData['time'] ?? 'N/A',
                'venue'        => $hardcodedData['venue'] ?? $formData['venue'] ?? 'N/A',
                'event_type'   => $hardcodedData['event_type'] ?? $formData['event_type'] ?? 'N/A',
                'theme_motif'  => $hardcodedData['theme_motif'] ?? $formData['theme_motif'] ?? 'N/A',
                'message'      => $hardcodedData['message'] ?? $formData['message'] ?? '',
            ];

            Mail::to($patron->email)->send(new ReservationSubmitted($emailData, null));

            return redirect()->back()->with('success', 'Inquiry successfully created!');
        } catch (\Throwable $th) {
            Log::error('Failed to insert inquiry: ' . $th->getMessage());
            return redirect()->back()->with('error', 'Failed to create inquiry. Please try again.');
        }
    }

    public function sendReply(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string',
            'inquiry_id' => 'required|exists:inquiry,inquiry_id',
        ]);

        $inquiry = Inquiry::with('patron')->findOrFail($request->inquiry_id);
        Log::info($inquiry);

        try {
            Mail::to($validated['email'])->send(new ReservationSubmitted(null, [
                'name'      => $inquiry->patron->name,
                'message'   => $validated['message'],
            ]));

            return response()->json(['success' => true, 'message' => 'Reply sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send reply: ' . $e->getMessage()]);
        }
    }

    public function fetchReservation(Request $request)
    {
        $code = $request->input('reservation_code');

        $inquiry = Inquiry::with('patron')->where('tracking_code', $code)->first();

        if (!$inquiry) {
            return response()->json(['success' => false, 'message' => 'Reservation not found.']);
        }

        Log::info($inquiry);

        $reservation = [
            'Name' => $inquiry->patron->name ?? 'N/A',
            'Email' => $inquiry->patron->email ?? '-',
            'Contact' => $inquiry->patron->contact_number ?? '-',
            'Date' => $inquiry->date ?? '-',
            'Time' => $inquiry->time ?? '-',
            'Venue' => $inquiry->venue === 'Others' ? $inquiry->other_venue : $inquiry->venue,
            'Event Type' => $inquiry->event_type === 'Others' ? $inquiry->other_event_type : $inquiry->event_type,
            'Theme & Motif' => $inquiry->theme_motif === 'Others' ? $inquiry->other_theme_motif : $inquiry->theme_motif,
            'Status' => $inquiry->status ?? 'Pending',
        ];

        // Merge dynamic form data if available
        if ($inquiry->form_data) {
            $formData = is_string($inquiry->form_data) ? json_decode($inquiry->form_data, true) : $inquiry->form_data;
            foreach ($formData as $key => $value) {
                // Only add if not already covered by hardcoded fields, or to supplement
                if (!isset($reservation[$key])) {
                    $reservation[ucfirst(str_replace('_', ' ', $key))] = $value;
                }
            }
        }

        return response()->json([
            'success' => true,
            'reservation' => $reservation,
        ]);
    }

    public function acceptGuestConsent(Request $request)
    {
        $guestToken = $request->cookie('guest_token') ?: Str::random(40);
        $expiresAt = now()->addDays(30);

        GuestConsent::updateOrCreate(
            ['guest_token' => $guestToken],
            [
                'consented_at' => now(),
                'expires_at' => $expiresAt,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $request->session()->put('guest_agreed', true);
        $request->session()->put('guest_token', $guestToken);

        return response()->json([
            'success' => true,
            'message' => 'Guest consent saved.',
            'expires_at' => $expiresAt->toDateTimeString(),
        ])->cookie('guest_token', $guestToken, 60 * 24 * 30);
    }

    public function renewGuest(Request $request)
    {
        $currentToken = $request->cookie('guest_token');
        $newToken = Str::random(40);

        // Only delete records belonging to this guest's token (or IP if no token)
        $query = GuestConsent::query();
        if ($currentToken) {
            $query->where('guest_token', $currentToken);
        } else {
            $query->where('ip_address', $request->ip());
        }
        $query->delete();

        $request->session()->forget(['guest_agreed', 'guest_token']);

        return redirect('/')
            ->with('success', 'Guest profile refreshed successfully.')
            ->cookie('guest_token', $newToken, 60 * 24 * 30);
    }
}
