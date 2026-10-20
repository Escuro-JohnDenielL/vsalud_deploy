<?php

namespace App\Http\Controllers;

use App\Services\WaitlistService;
use App\Rules\EmailAddress;
use App\Support\PatronAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        // Retire any expired offer for this date before deciding, so a patron
        // holding a lapsed offer is not told they are already queued. A sweep
        // failure (cache or mail) must not stop the join itself.
        try {
            $this->waitlistService->sweepExpiredNotifications();
        } catch (\Throwable $e) {
            Log::warning('Waitlist: join sweep skipped: ' . $e->getMessage());
        }

        $validated = $request->validate([
            'patron_name'  => 'required|string|max:255',
            'patron_email' => ['required', 'string', 'max:254', new EmailAddress()],
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

        // Only a real join is audited; a rejected duplicate or an expired
        // re-join attempt stays out of the audit trail.
        if (($result['success'] ?? false) === true) {
            PatronAudit::log(
                'Patron Waitlist Joined',
                $request,
                null,
                'joined the waitlist for ' . $validated['date']
            );
        }

        return response()->json($result);
    }
}
