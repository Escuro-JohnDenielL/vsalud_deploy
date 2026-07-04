<?php

namespace App\Http\Controllers\Patron;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Inquiry;
use App\Models\Reservation;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index()
    {
        return view('patron.payment');
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //         'tracking_code' => 'required|string',
    //         'reference_code' => 'nullable|string|max:100',
    //         'receipt' => 'required|mimes:jpg,jpeg,png,pdf|max:2048',
    //     ]);

    //     $inquiry = Inquiry::where('tracking_code', $request->tracking_code)
    //         ->whereHas('patron', fn($q) => $q->where('email', $request->email))
    //         ->first();

    //     if (!$inquiry) {
    //         return back()->with('error', 'Invalid email or tracking code.');
    //     }

    //     $reservation = Reservation::where('inquiry_id', $inquiry->id)->first();

    //     if (!$reservation) {
    //         return back()->with('error', 'No reservation found for this tracking code.');
    //     }

    //     $path = $request->file('receipt')->store('payments', 'public');

    //     Payment::create([
    //         'reserve_id' => $reservation->id,
    //         'reference_code' => $request->reference_code,
    //         'receipt_path' => $path,
    //         'status' => 'pending',
    //     ]);

    //     return back()->with('success', 'Receipt uploaded successfully. Your payment is now under review.');
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|min:2',
            'payment_type' => 'required|in:full,half',
            'payment_method' => 'required|in:cash,gcash,bank',
            'email' => 'required|email',
            'reservation_code' => 'required|string|exists:inquiry,tracking_code',
            'receipt' => 'required|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Verify the reservation code belongs to the same email
        $inquiry = Inquiry::where('tracking_code', $validated['reservation_code'])->first();
        if ($inquiry && $inquiry->patron) {
            if (strtolower($inquiry->patron->email) !== strtolower($validated['email'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'The reservation code does not match the provided email address.',
                ], 422);
            }
        }

        $trackingCode = 'VS-' . substr(time(), -6) . '-' . rand(1000, 9999);

        // Store receipt
        $receiptPath = $request->file('receipt')->store('receipts', 'public');

        $payment = Payment::create([
            'full_name' => $validated['full_name'],
            'payment_type' => $validated['payment_type'],
            'payment_method' => $validated['payment_method'],
            'tracking_code' => $trackingCode,
            'reservation_code' => $validated['reservation_code'],
            'inquiry_id' => $inquiry?->inquiry_id,
            'receipt_path' => $receiptPath,
            'email' => $validated['email'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'tracking_code' => $trackingCode,
        ]);
    }
}
