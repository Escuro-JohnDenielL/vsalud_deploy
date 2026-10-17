<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Services\AvailabilityService;
use App\Services\VenueOccupancyService;
use App\Support\AvailabilityScale;
use App\Support\VenueCatalog;
use App\Support\VenueSlot;
use Illuminate\Http\Request;

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

        $occupancy = app(VenueOccupancyService::class)->occupiedPeriodsByVenue($date);
        $eventCount = app(AvailabilityService::class)->activeEventCount($date);
        $dailyCap = AvailabilityScale::cap();

        // A date the admin marked Closed blocks every venue.
        $closed = Availability::query()
            ->where('date', '>=', $date)
            ->where('date', '<', \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString())
            ->where('is_override', true)
            ->where('status', 'Closed')
            ->exists();

        $venues = [];

        foreach (VenueCatalog::names() as $name) {
            $key = VenueSlot::normalize($name);

            $venues[] = [
                'name' => $name,
                'occupied' => [
                    'AM' => (bool) ($occupancy[$key]['AM'] ?? false),
                    'PM' => (bool) ($occupancy[$key]['PM'] ?? false),
                ],
            ];
        }

        return response()->json([
            'date' => $date,
            'closed' => $closed,
            'count' => $eventCount,
            'daily_cap' => $dailyCap,
            'at_capacity' => $eventCount >= $dailyCap,
            'period' => VenueSlot::normalizePeriod($validated['period'] ?? null),
            'venues' => $venues,
        ]);
    }
}
