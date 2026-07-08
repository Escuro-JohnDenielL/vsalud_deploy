<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,refunded',
        ]);

        $payment->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated to ' . $validated['status'] . '.',
        ]);
    }
}
