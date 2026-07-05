<?php

namespace App\Http\Controllers;

use App\Services\WaitlistService;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    protected $waitlistService;

    public function __construct(WaitlistService $waitlistService)
    {
        $this->waitlistService = $waitlistService;
    }

    /**
     * Join the waitlist for a date.
     */
    public function join(Request $request)
    {
        $validated = $request->validate([
            'patron_name'  => 'required|string|max:255',
            'patron_email' => 'required|email|max:255',
            'date'         => 'required|date',
        ]);

        // Verify the date is actually full
        if (!$this->waitlistService->isDateFull($validated['date'])) {
            return response()->json([
                'success' => false,
                'message' => 'This date still has availability. Please submit a reservation directly.',
            ]);
        }

        $result = $this->waitlistService->join(
            $validated['patron_name'],
            $validated['patron_email'],
            $validated['date']
        );

        return response()->json($result);
    }
}
