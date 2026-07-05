<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;

class WaitlistController extends Controller
{
    /**
     * Show the waitlist (read-only) grouped by date.
     */
    public function index()
    {
        $entries = Waitlist::orderBy('requested_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by date for easier reading
        $grouped = $entries->groupBy(function ($entry) {
            return $entry->requested_date->format('Y-m-d');
        });

        // Summary stats
        $stats = [
            'total'    => $entries->count(),
            'waiting'  => $entries->where('status', 'waiting')->count(),
            'notified' => $entries->where('status', 'notified')->count(),
            'claimed'  => $entries->where('status', 'claimed')->count(),
            'expired'  => $entries->where('status', 'expired')->count(),
        ];

        return view('admin.waitlist', compact('grouped', 'stats'));
    }
}
