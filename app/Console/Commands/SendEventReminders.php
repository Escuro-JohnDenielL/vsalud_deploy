<?php

namespace App\Console\Commands;

use App\Mail\EventReminder;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:send-event-reminders';

    /**
     * The console command description.
     */
    protected $description = 'Send event reminders to patrons with reservations 7 days away';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for reservations 7 days away...');

        $targetDate = Carbon::today()->addDays(7)->toDateString();
        $sentCount = 0;

        $reservations = Reservation::where('status', 'active')
            ->whereDate('date', $targetDate)
            ->whereNull('event_reminder_sent_at')
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

            Mail::to($patron->email)->send(new EventReminder($emailData));

            $reservation->update(['event_reminder_sent_at' => now()]);
            $sentCount++;

            $this->info("Event reminder sent to {$patron->email} for reservation #{$reservation->reserve_id}.");
        }

        $this->info("Sent {$sentCount} event reminder(s).");

        return Command::SUCCESS;
    }
}
