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
            ->paginate(10);

        // Group by date for easier reading
        $grouped = $entries->getCollection()->groupBy(function ($entry) {
            return $entry->requested_date->format('Y-m-d');
        });

        // Summary stats (use all entries for accurate stats)
        $allEntries = Waitlist::select('status')->get();
        $stats = [
            'total'    => $allEntries->count(),
            'waiting'  => $allEntries->where('status', 'waiting')->count(),
            'notified' => $allEntries->where('status', 'notified')->count(),
            'claimed'  => $allEntries->where('status', 'claimed')->count(),
            'expired'  => $allEntries->where('status', 'expired')->count(),
        ];

        return view('admin.waitlist', compact('entries', 'grouped', 'stats'));
    }
}
