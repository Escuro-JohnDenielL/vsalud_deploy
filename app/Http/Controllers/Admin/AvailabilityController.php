<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Availability;
use App\Services\AvailabilityService;
use App\Support\AvailabilityOverrideRule;
use App\Support\AvailabilityScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvailabilityController extends Controller
{

    public function index()
    {
        $records = \App\Models\Availability::all()->mapWithKeys(function ($item) {
            if ($item->date === null) {
                return [];
            }
            return [$item->date->format('Y-n-j') => [
                'status' => $item->status,
                'is_override' => $item->is_override,
            ]];
        });

        return response()->json($records);
    }


    /**
     * Save an admin override for one date.
     *
     * Everything saved here is an override (is_override is forced to true), so
     * the date stops following the computed slot count until the override is
     * removed. Two overrides are refused outright:
     *
     *   - Available on a date that already holds at least the daily cap worth
     *     of bookings, because advertising it as free would be untrue;
     *   - Closed on a date that still holds bookings, unless the admin sends
     *     a reason and confirms the exact number of bookings being kept.
     *
     * Closing never cancels anything: it only stops new bookings on that date.
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:Available,Half,Nearly,Full,Closed',
            'override' => 'sometimes|boolean',
            'reason' => 'nullable|string|max:255',
            'confirmed_count' => 'nullable|integer|min:0',
        ]);

        $date = $validated['date'];
        // Open inquiries already hold a slot, so they count as bookings here.
        $booked = app(AvailabilityService::class)->bookedEventCount($date);
        $cap = AvailabilityScale::cap();

        $decision = AvailabilityOverrideRule::evaluate(
            $validated['status'],
            $booked,
            $cap,
            $validated['reason'] ?? null,
            array_key_exists('confirmed_count', $validated) && $validated['confirmed_count'] !== null
                ? (int) $validated['confirmed_count']
                : null
        );

        if ($decision !== AvailabilityOverrideRule::OK) {
            return response()->json([
                'success' => false,
                'code' => $decision,
                'count' => $booked,
                'message' => AvailabilityOverrideRule::message($decision, $booked, $cap),
            ], 422);
        }

        $availability = Availability::updateOrCreate(
            ['date' => $date],
            [
                'status' => $validated['status'],
                'is_override' => true,
            ]
        );

        // Closing a date that still holds bookings is the one override with a
        // consequence worth reading later, so it gets its own audit entry with
        // the reason and the count. Every other save is covered by the generic
        // admin activity entry written for this request.
        if ($validated['status'] === 'Closed' && $booked > 0) {
            $admin = Auth::guard('admin')->user();
            $name = trim((string) ($admin->f_name ?? '') . ' ' . (string) ($admin->l_name ?? ''))
                ?: (string) ($admin->email ?? 'Admin');

            $dateLabel = $availability->date
                ? $availability->date->format('Y-m-d')
                : (string) $date;

            ActivityLog::create([
                'admin_id' => $admin?->getAuthIdentifier() ?? 0,
                'activity_type' => 'Closed Date',
                'category' => 'config',
                'route_name' => 'admin.availability.toggle',
                'http_method' => $request->method(),
                'subject' => 'Closed ' . $dateLabel,
                'description' => "Admin {$name} closed {$dateLabel} with {$booked} booking(s), which were not cancelled. Reason: "
                    . trim((string) $validated['reason']),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]);
        }

        return response()->json([
            'success' => true,
            'date' => $availability->date,
            'status' => $availability->status,
            'is_override' => $availability->is_override,
            'booked' => $booked,
        ]);
    }


    /**
     * Remove the override for one date.
     *
     * The row is deleted rather than flagged, so the date falls back to the
     * computed slot count. Only the requested date is touched.
     */
    public function removeOverride(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        \App\Models\Availability::where('date', $request->date)->delete();

        return response()->json([
            'success' => true,
            'date' => $request->date,
            'is_override' => false,
        ]);
    }
}
