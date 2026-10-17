<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    /**
     * Show the waitlist (read-only) grouped by date.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $entries = Waitlist::when($search !== '', function ($query) use ($search) {
            $like = '%' . $search . '%';

            $query->where(function ($group) use ($like) {
                $group->where('patron_name', 'like', $like)
                    ->orWhere('patron_email', 'like', $like);
            });
        })
            ->orderBy('requested_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

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

        return view('admin.waitlist', compact('entries', 'grouped', 'stats', 'search'));
    }
}
