<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patron;
use App\Models\Inquiry;
use App\Models\GuestConsent;
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
        Log::info('may data', $request->all());
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'event_type'     => 'required|string',
            'theme_motif'    => 'required|string',
            'venue'          => 'required|string',
            'date'           => 'required|date',
            'time'           => 'required',
            'message'        => 'required|string',
        ]);

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name'           => $validated['name'],
                'contact_number' => $validated['contact_number'],
            ]
        );

        $eventType     = $validated['event_type'];
        $themeMotif    = $validated['theme_motif'];
        $venue         = $validated['venue'];

        $isEventOther  = strtolower(trim($eventType)) === 'others';
        $isThemeOther  = strtolower(trim($themeMotif)) === 'others';
        $isVenueOther  = strtolower(trim($venue)) === 'others';

        try {
            $inquiry = Inquiry::create([
                'patron_id'         => $patron->patron_id,
                'event_type'        => $isEventOther ? 'Others' : $eventType,    // Fixed: 'Others' not 'Other'
                'other_event_type'  => $isEventOther ? $request->input('other_event_type') : null,
                'theme_motif'       => $isThemeOther ? 'Others' : $themeMotif,   // Fixed: 'Others' not 'Other'
                'other_theme_motif' => $isThemeOther ? $request->input('other_theme_motif') : null,
                'venue'             => $isVenueOther ? 'Others' : $venue,        // Fixed: 'Others' not 'Other'
                'other_venue'       => $isVenueOther ? $request->input('other_venue') : null,
                'date'              => $validated['date'],
                'time'              => $validated['time'],
                'message'           => $validated['message'],
                'status'            => 'Pending',  // Also fixed capitalization to match enum
            ]);

            Mail::to($validated['email'])->send(new ReservationSubmitted([
                'name'        => $validated['name'],
                'date'        => $validated['date'],
                'time'        => $validated['time'],
                'venue'       => $venue,
                'event_type'  => $eventType,
                'theme_motif' => $themeMotif,
                'message' => $validated['message'],
                'tracking_code' => $inquiry['tracking_code'],
            ], null));

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

        return response()->json([
            'success' => true,
            'reservation' => [
                'Name' => $inquiry->patron->name ?? 'N/A',
                'Email' => $inquiry->patron->email ?? '-',
                'Contact' => $inquiry->patron->contact_number ?? '-',
                'Date' => $inquiry->date ?? '-',
                'Time' => $inquiry->time ?? '-',
                'Venue' => $inquiry->venue === 'Others' ? $inquiry->other_venue : $inquiry->venue,
                'Event Type' => $inquiry->event_type === 'Others' ? $inquiry->other_event_type : $inquiry->event_type,
                'Theme & Motif' => $inquiry->theme_motif === 'Others' ? $inquiry->other_theme_motif : $inquiry->theme_motif,
                'Status' => $inquiry->status ?? 'Pending',
            ]
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
