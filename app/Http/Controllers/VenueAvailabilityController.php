<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Services\VenueOccupancyService;
use App\Support\AvailabilityScale;
use App\Support\VenueSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Public venue availability for the "Venues on <date>" panel.
 *
 * The payload carries only the venue name, the periods and whether each one is
 * occupied. No patron, inquiry or reservation data is exposed, and the endpoint
 * is throttled by the "venue-availability" named limiter. It is a display aid:
 * the slot is always rechecked on the server when the form is submitted.
 */
class VenueAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            // Accepted so the caller can state its context; the response always
            // reports both periods so the panel can refresh without another call.
            'period' => 'sometimes|nullable|string|max:4',
        ]);

        $date = (string) $validated['date'];

        $occupancyService = app(VenueOccupancyService::class);

        // One bookings query feeds both the event count and the venue flags.
        $reservations = $occupancyService->activeForDate($date);

        // A date the admin marked Closed blocks every venue.
        $closed = Availability::query()
            ->where('date', '>=', $date)
            ->where('date', '<', Carbon::parse($date)->addDay()->toDateString())
            ->where('is_override', true)
            ->where('status', 'Closed')
            ->exists();

        $panel = $occupancyService->panelPayload($closed, $reservations);
        $dailyCap = AvailabilityScale::cap();

        return response()->json([
            'date' => $date,
            'closed' => $panel['closed'],
            'count' => $panel['count'],
            'daily_cap' => $dailyCap,
            'at_capacity' => $panel['count'] >= $dailyCap,
            'period' => VenueSlot::normalizePeriod($validated['period'] ?? null),
            'venues' => $panel['venues'],
        ]);
    }
}
