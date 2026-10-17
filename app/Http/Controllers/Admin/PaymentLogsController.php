<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentLogsController extends Controller
{
    /**
     * Display a listing of payment logs.
     */
    public function index(Request $request)
    {
        $payments = Payment::orderByDesc('payment_id')->get();
        $inquiryIds = $payments->pluck('inquiry_id')->filter()->unique();
        $reservationCodes = $payments->pluck('reservation_code')
            ->filter(fn ($code) => $code !== null && $code !== '')
            ->unique();

        $reservationsByInquiry = Reservation::with(['inquiry', 'patron'])
            ->whereIn('inquiry_id', $inquiryIds)
            ->orderByDesc('reserve_id')
            ->get()
            ->keyBy('inquiry_id');

        $reservationsByCode = Reservation::with(['inquiry', 'patron'])
            ->whereHas('inquiry', fn ($query) => $query->whereIn('tracking_code', $reservationCodes))
            ->orderByDesc('reserve_id')
            ->get()
            ->filter(fn (Reservation $reservation) => $reservation->inquiry?->tracking_code !== null)
            ->keyBy(fn (Reservation $reservation) => $reservation->inquiry->tracking_code);

        $approvedByInquiry = [];
        foreach ($payments as $payment) {
            if ($payment->inquiry_id && $payment->status === 'approved' && $payment->amount !== null) {
                $key = $payment->inquiry_id;
                $approvedByInquiry[$key] = bcadd(
                    $approvedByInquiry[$key] ?? '0.00',
                    (string) $payment->amount,
                    2
                );
            }
        }

        $entries = [];
        $groupIds = 0;

        foreach ($payments as $payment) {
            $code = $payment->reservation_code;
            $reservation = $code !== null && $code !== '' ? $reservationsByCode->get($code) : null;

            if ($reservation && $reservation->total_amount !== null) {
                $entryKey = 'reservation:' . $code;
                if (! isset($entries[$entryKey])) {
                    $entries[$entryKey] = [
                        'kind' => 'reservation',
                        'dom_id' => 'payment-group-' . (++$groupIds),
                        'sort_id' => $payment->payment_id,
                        'reservation_code' => $code,
                        'reservation' => $reservation,
                        'payments' => collect(),
                    ];
                }
                $entries[$entryKey]['payments']->push($payment);
                continue;
            }

            $linkedReservation = $payment->inquiry_id
                ? $reservationsByInquiry->get($payment->inquiry_id)
                : null;
            $total = $linkedReservation?->total_amount;
            $paid = $total === null
                ? null
                : bcadd($approvedByInquiry[$payment->inquiry_id] ?? '0.00', '0', 2);

            $entries['payment:' . $payment->payment_id] = [
                'kind' => 'standalone',
                'sort_id' => $payment->payment_id,
                'payment' => $payment,
                'total_display' => $total === null ? '-' : $this->formatMoney((string) $total),
                'paid_display' => $paid === null ? '-' : $this->formatMoney($paid),
                'remaining_display' => $paid === null
                    ? '-'
                    : $this->formatMoney(bcsub((string) $total, $paid, 2)),
            ];
        }

        $rawSearch = $request->query('search', '');
        $search = is_string($rawSearch) ? trim($rawSearch) : '';
        $searchNeedle = mb_strtolower($search);
        $groupedEntries = collect($entries)
            ->sortByDesc('sort_id')
            ->values()
            ->map(function (array $entry) {
                if ($entry['kind'] === 'standalone') {
                    $payment = $entry['payment'];
                    $entry['amount_display'] = $this->formatPaymentAmount($payment);
                    return $entry;
                }

                $approvedPaid = '0.00';
                $pendingCount = 0;
                foreach ($entry['payments'] as $payment) {
                    if ($payment->status === 'approved' && $payment->amount !== null) {
                        $approvedPaid = bcadd($approvedPaid, (string) $payment->amount, 2);
                    }
                    if ($payment->status === 'pending') {
                        $pendingCount++;
                    }
                    $payment->amount_display = $this->formatPaymentAmount($payment);
                }

                $total = (string) $entry['reservation']->total_amount;
                $remaining = bcsub($total, $approvedPaid, 2);
                $entry['total_display'] = $this->formatMoney($total);
                $entry['paid_display'] = $this->formatMoney($approvedPaid);
                $entry['remaining_display'] = $this->formatMoney($remaining);
                $entry['pending_count'] = $pendingCount;
                $entry['payment_count'] = $entry['payments']->count();
                $entry['name'] = $entry['reservation']->patron->name
                    ?? $entry['payments']->first()?->full_name
                    ?? '-';
                $entry['email'] = $entry['reservation']->patron->email
                    ?? $entry['payments']->first()?->email
                    ?? '-';
                $entry['confirmation_label'] = $this->confirmationLabel($total, $approvedPaid, $remaining);

                return $entry;
            })
            ->filter(function (array $entry) use ($searchNeedle) {
                if ($searchNeedle === '') {
                    return true;
                }

                if ($entry['kind'] === 'reservation') {
                    $values = [$entry['reservation_code'], $entry['name'], $entry['email']];
                } else {
                    $payment = $entry['payment'];
                    $values = [$payment->reservation_code, $payment->full_name, $payment->email];
                }

                foreach ($values as $value) {
                    if ($value !== null && str_contains(mb_strtolower((string) $value), $searchNeedle)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();

        $perPage = 10;
        $pageValue = $request->query('page', 1);
        $page = is_scalar($pageValue) && ctype_digit((string) $pageValue)
            ? max(1, (int) $pageValue)
            : 1;
        $payment_groups = new LengthAwarePaginator(
            $groupedEntries->forPage($page, $perPage)->values(),
            $groupedEntries->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        );
        $payment_groups->appends($request->except('page'));

        return view('admin.payment-logs', compact('payment_groups', 'search'));
    }

    private function formatPaymentAmount(Payment $payment): string
    {
        return $payment->amount === null
            ? 'amount not recorded'
            : $this->formatMoney((string) $payment->amount);
    }

    private function confirmationLabel(string $total, string $paid, string $remaining): string
    {
        if (bccomp($remaining, '0.00', 2) === 0) {
            return 'Fully paid';
        }

        if (bccomp($paid, '0.00', 2) === 0) {
            return 'No payment yet';
        }

        if (bccomp($paid, $this->defaultDownpayment($total), 2) >= 0) {
            return 'Downpayment received';
        }

        return 'No payment yet';
    }

    private function defaultDownpayment(string $total): string
    {
        return \App\Support\PaymentAmount::downpayment($total);
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
     * the receipt always stays behind admin auth. New receipts go to the public
     * disk (Railway volume mounted at /var/www/storage/app/public), with R2 and
     * the private local disk as fallbacks for older rows. Receipts are tied to
     * a Payment row in the DB.
     */
    public function showReceipt(Payment $payment)
    {
        $path = $payment->receipt_path;

        if (! $path) {
            abort(404, 'Receipt file not found.');
        }

        // Check the public disk first (fastest, same server, and where new
        // uploads go), then R2 and the private local disk, so past rows still
        // resolve and one disk failing never 500s the request.
        foreach (['public', 'r2', 'local'] as $diskName) {
            try {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk($diskName);

                if ($disk->exists($path)) {
                    $mime = $disk->mimeType($path) ?: 'application/octet-stream';

                    return $disk->response($path, null, [
                        'Content-Type' => $mime,
                        'X-Content-Type-Options' => 'nosniff',
                        'Content-Disposition' => 'inline',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Receipt lookup failed on disk [{$diskName}]: " . $e->getMessage());
                continue;
            }
        }

        abort(404, 'Receipt file not found.');
    }
}
