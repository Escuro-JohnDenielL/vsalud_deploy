<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
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

        $inquiryIds = $payment_logs->getCollection()->pluck('inquiry_id')->filter()->unique();
        $reservations = Reservation::whereIn('inquiry_id', $inquiryIds)->get()->keyBy('inquiry_id');
        $paidTotals = Payment::query()
            ->whereIn('inquiry_id', $inquiryIds)
            ->where('status', 'approved')
            ->whereNotNull('amount')
            ->selectRaw('inquiry_id, CAST(SUM(amount) AS CHAR) AS paid_total')
            ->groupBy('inquiry_id')
            ->get()
            ->keyBy('inquiry_id');

        $payment_logs->getCollection()->transform(function (Payment $payment) use ($reservations, $paidTotals) {
            $reservation = $reservations->get($payment->inquiry_id);
            $total = $reservation?->total_amount;
            $paid = $total === null
                ? null
                : bcadd((string) ($paidTotals->get($payment->inquiry_id)->paid_total ?? '0'), '0', 2);

            $payment->amount_display = $payment->amount === null
                ? 'amount not recorded'
                : $this->formatMoney((string) $payment->amount);
            $payment->paid_display = $paid === null ? '-' : $this->formatMoney($paid);
            $payment->remaining_display = $paid === null
                ? '-'
                : $this->formatMoney(bcsub((string) $total, $paid, 2));

            return $payment;
        });

        return view('admin.payment-logs', compact('payment_logs'));
    }

    private function formatMoney(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '00');
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole);

        return $whole . '.' . str_pad(substr($fraction, 0, 2), 2, '0');
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
