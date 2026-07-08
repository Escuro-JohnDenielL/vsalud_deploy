<?php

namespace App\Console\Commands;

use App\Mail\PaymentReminder;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:send-payment-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send payment reminders for reservations created 3+ days ago with pending/unpaid status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for reservations with pending payment...');

        $cutoffDate = Carbon::now()->subDays(3);
        $sentCount = 0;

        // Find active reservations created 3+ days ago that haven't been sent a
        // payment reminder yet, and have no approved payment on file.
        $reservations = Reservation::where('reservation.status', 'active')
            ->where('reservation.created_at', '<=', $cutoffDate)
            ->whereNull('reservation.payment_reminder_sent_at')
            ->whereDoesntHave('inquiry.payments', function ($query) {
                $query->where('status', 'approved');
            })
            ->get();

        foreach ($reservations as $reservation) {
            $patron = $reservation->patron;
            $inquiry = $reservation->inquiry;

            if (! $patron || ! $patron->email) {
                $this->warn("Reservation #{$reservation->reserve_id}: No patron email found, skipping.");
                continue;
            }

            $emailData = [
                'name'           => $patron->name,
                'tracking_code'  => $inquiry?->tracking_code ?? 'N/A',
                'date'           => $reservation->date,
                'time'           => $reservation->time,
                'venue'          => $reservation->venue,
                'event_type'     => $reservation->event_type,
                'theme_motif'    => $reservation->theme_motif,
                'message'        => $reservation->message,
            ];

            Mail::to($patron->email)->send(new PaymentReminder($emailData));

            $reservation->update(['payment_reminder_sent_at' => now()]);
            $sentCount++;

            $this->info("Payment reminder sent to {$patron->email} for reservation #{$reservation->reserve_id}.");
        }

        $this->info("Sent {$sentCount} payment reminder(s).");

        return Command::SUCCESS;
    }
}
