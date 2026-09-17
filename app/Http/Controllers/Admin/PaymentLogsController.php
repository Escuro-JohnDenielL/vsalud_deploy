<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Stream a payment receipt (image or PDF) directly from the storage disk.
     *
     * Reads through the Storage facade instead of serving the /storage URL, so
     * the receipt always stays behind admin auth. The primary disk is "public",
     * backed by the Railway volume mounted at /var/www/storage/app/public.
     * Receipts are tied to a Payment row in the DB.
     */
    public function showReceipt(Payment $payment)
    {
        $path = $payment->receipt_path;

        if (! $path) {
            abort(404, 'Receipt file not found.');
        }

        // New uploads live on the "public" disk (the Railway volume mounted at
        // /var/www/storage/app/public), so check it first. R2 (older cloud
        // uploads) and the private local disk remain as fallbacks so past rows
        // still resolve, and one disk failing never 500s the request.
        foreach (['public', 'r2', 'local'] as $diskName) {
            try {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk($diskName);

                if ($disk->exists($path)) {
                    return $disk->response($path);
                }
            } catch (\Throwable $e) {
                Log::warning("Receipt lookup failed on disk [{$diskName}]: " . $e->getMessage());
                continue;
            }
        }

        abort(404, 'Receipt file not found.');
    }
}
