<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentLogsController extends Controller
{
    /**
     * Display a listing of payment logs.
     */
    public function index()
    {
        $payment_logs = Payment::latest()->paginate(10);

        return view('admin.payment-logs', compact('payment_logs'));
    }
}
