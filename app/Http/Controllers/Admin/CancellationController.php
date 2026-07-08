<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CancellationRequest;
use App\Models\Reservation;
use App\Models\Inquiry;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\CancellationApproved;
use App\Mail\CancellationDenied;
use App\Services\WaitlistService;
use Illuminate\Support\Facades\Log;

class CancellationController extends Controller
{
    /**
     * Show all cancellation requests.
     */
    public function index()
    {
        $requests = CancellationRequest::with(['reservation.patron', 'reservation.inquiry', 'admin'])
            ->latest()
            ->get();

        return view('admin.cancellations', compact('requests'));
    }

    /**
     * Approve a cancellation request.
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $cancelRequest = CancellationRequest::with('reservation.inquiry')->findOrFail($id);

        if ($cancelRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed.',
            ]);
        }

        $admin = Auth::guard('admin')->user();
        $reservation = $cancelRequest->reservation;
        $inquiry = $reservation ? Inquiry::find($reservation->inquiry_id) : null;

        try {
            // Update cancellation request
            $cancelRequest->status = 'approved';
            $cancelRequest->admin_id = $admin->admin_id;
            $cancelRequest->admin_note = $request->admin_note;
            $cancelRequest->save();

            // Update reservation status
            if ($reservation) {
                $reservation->status = 'cancelled';
                $reservation->save();
            }

            // Update inquiry status
            if ($inquiry) {
                $inquiry->status = 'Cancelled';
                $inquiry->save();
            }

            // Notify the next person on the waitlist (slot freed up)
            if ($reservation && $reservation->date) {
                try {
                    $waitlistService = app(WaitlistService::class);
                    $waitlistService->notifyNextForDate($reservation->date);
                } catch (\Exception $e) {
                    Log::error('Failed to process waitlist after cancellation: ' . $e->getMessage());
                }
            }

            // Log activity
            ActivityLog::create([
                'admin_id'      => $admin->admin_id,
                'activity_type' => 'Cancellation Approved',
                'description'   => "Admin {$admin->f_name} {$admin->l_name} approved cancellation request #{$cancelRequest->id} for reservation #{$reservation->reserve_id}",
                'reserve_id'    => $reservation ? $reservation->reserve_id : null,
                'inquiry_id'    => $inquiry ? $inquiry->inquiry_id : null,
            ]);

            // Send approval email
            $emailData = [
                'name'          => $cancelRequest->patron_email,
                'tracking_code' => $inquiry->tracking_code ?? 'N/A',
                'date'          => $reservation->date ?? 'N/A',
                'time'          => $reservation->time ?? 'N/A',
                'venue'         => $reservation->venue ?? 'N/A',
                'event_type'    => $reservation->event_type ?? 'N/A',
                'theme_motif'   => $reservation->theme_motif ?? 'N/A',
                'admin_note'    => $request->admin_note,
            ];

            // Try to get patron name for a more personal email
            if ($reservation && $reservation->patron) {
                $emailData['name'] = $reservation->patron->name;
            }

            Mail::to($cancelRequest->patron_email)->send(new CancellationApproved($emailData));

            return response()->json([
                'success' => true,
                'message' => 'Cancellation request approved. Reservation has been cancelled.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve cancellation: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve cancellation. Please try again.',
            ]);
        }
    }

    /**
     * Deny a cancellation request.
     */
    public function deny(Request $request, $id)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $cancelRequest = CancellationRequest::with('reservation.inquiry')->findOrFail($id);

        if ($cancelRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed.',
            ]);
        }

        $admin = Auth::guard('admin')->user();
        $reservation = $cancelRequest->reservation;
        $inquiry = $reservation ? Inquiry::find($reservation->inquiry_id) : null;

        try {
            // Update cancellation request
            $cancelRequest->status = 'denied';
            $cancelRequest->admin_id = $admin->admin_id;
            $cancelRequest->admin_note = $request->admin_note;
            $cancelRequest->save();

            // Log activity
            ActivityLog::create([
                'admin_id'      => $admin->admin_id,
                'activity_type' => 'Cancellation Denied',
                'description'   => "Admin {$admin->f_name} {$admin->l_name} denied cancellation request #{$cancelRequest->id} for reservation #{$reservation->reserve_id}",
                'reserve_id'    => $reservation ? $reservation->reserve_id : null,
                'inquiry_id'    => $inquiry ? $inquiry->inquiry_id : null,
            ]);

            // Send denial email
            $emailData = [
                'name'          => $cancelRequest->patron_email,
                'tracking_code' => $inquiry->tracking_code ?? 'N/A',
                'date'          => $reservation->date ?? 'N/A',
                'time'          => $reservation->time ?? 'N/A',
                'venue'         => $reservation->venue ?? 'N/A',
                'event_type'    => $reservation->event_type ?? 'N/A',
                'theme_motif'   => $reservation->theme_motif ?? 'N/A',
                'admin_note'    => $request->admin_note,
            ];

            if ($reservation && $reservation->patron) {
                $emailData['name'] = $reservation->patron->name;
            }

            Mail::to($cancelRequest->patron_email)->send(new CancellationDenied($emailData));

            return response()->json([
                'success' => true,
                'message' => 'Cancellation request denied.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to deny cancellation: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to deny cancellation. Please try again.',
            ]);
        }
    }
}
