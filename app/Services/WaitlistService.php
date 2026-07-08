<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Mail;
use App\Mail\WaitlistSlotAvailable;
use App\Mail\WaitlistSlotExpired;
use Illuminate\Support\Facades\Log;

class WaitlistService
{
    /**
     * Notify the next person on the waitlist for a given date.
     * Called when a slot frees up (e.g., cancellation approved).
     *
     * @param string $date The date (Y-m-d) that now has a free slot.
     * @return bool Whether anyone was notified.
     */
    public function notifyNextForDate(string $date): bool
    {
        $next = Waitlist::nextWaitingForDate($date);

        if (!$next) {
            return false; // No one waiting
        }

        $next->status = 'notified';
        $next->notified_at = now();
        $next->save();

        try {
            Mail::to($next->patron_email)->send(new WaitlistSlotAvailable([
                'name'           => $next->patron_name,
                'date'           => $next->requested_date->format('Y-m-d'),
                'hours'          => 24,
            ]));

            Log::info("Waitlist: Notified {$next->patron_email} for date {$date}");
            return true;
        } catch (\Exception $e) {
            Log::error("Waitlist: Failed to notify {$next->patron_email} for date {$date}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check for expired notifications and rotate to the next person.
     * Called by the scheduled command.
     *
     * @return int Number of expirations processed.
     */
    public function processExpiredNotifications(): int
    {
        $expired = Waitlist::expiredNotifications();
        $count = 0;

        foreach ($expired as $entry) {
            $date = $entry->requested_date->format('Y-m-d');

            // Mark as expired
            $entry->status = 'expired';
            $entry->save();

            // Notify the patron that their window expired
            try {
                Mail::to($entry->patron_email)->send(new WaitlistSlotExpired([
                    'name' => $entry->patron_name,
                    'date' => $entry->requested_date->format('Y-m-d'),
                ]));
            } catch (\Exception $e) {
                Log::error("Waitlist: Failed to send expiry notice to {$entry->patron_email}: " . $e->getMessage());
            }

            // Rotate to next person on the waitlist for this date
            $this->notifyNextForDate($date);
            $count++;
        }

        return $count;
    }

    /**
     * Check if a date is currently full (per the availability system).
     *
     * @param string $date Y-m-d format
     * @return bool
     */
    public function isDateFull(string $date): bool
    {
        $maxInquiries = config('availability.max_inquiries', 4);
        $count = Inquiry::where('date', $date)
            ->where('status', '!=', 'Cancelled')
            ->count();

        return $count >= $maxInquiries;
    }

    /**
     * Join the waitlist for a date.
     *
     * @param string $name
     * @param string $email
     * @param string $date Y-m-d
     * @return array ['success' => bool, 'message' => string]
     */
    public function join(string $name, string $email, string $date): array
    {
        // Check if already on the waitlist for this date
        $existing = Waitlist::forDate($date)
            ->where('patron_email', $email)
            ->first();

        if ($existing) {
            $statusLabels = [
                'waiting' => 'You are already on the waitlist for this date.',
                'notified' => 'You have already been notified about an opening for this date. Please check your email.',
                'claimed' => 'You have already claimed a slot for this date.',
                'expired' => 'Your previous waitlist entry for this date has expired. You can join again.',
            ];

            // Allow re-joining if expired
            if ($existing->status === 'expired') {
                $existing->status = 'waiting';
                $existing->notified_at = null;
                $existing->claimed_at = null;
                $existing->save();

                return ['success' => true, 'message' => 'You have been re-added to the waitlist.'];
            }

            return ['success' => false, 'message' => $statusLabels[$existing->status] ?? 'You are already on the waitlist.'];
        }

        Waitlist::create([
            'patron_name'    => $name,
            'patron_email'   => $email,
            'requested_date' => $date,
            'status'         => 'waiting',
        ]);

        Log::info("Waitlist: {$email} joined waitlist for {$date}");

        return ['success' => true, 'message' => 'You have been added to the waitlist. We will email you if a slot opens up.'];
    }

    /**
     * Mark a waitlist entry as claimed when a reservation is submitted.
     *
     * @param string $email
     * @param string $date Y-m-d
     * @return bool
     */
    public function markAsClaimed(string $email, string $date): bool
    {
        $entry = Waitlist::forDate($date)
            ->where('patron_email', $email)
            ->where('status', 'notified')
            ->first();

        if (!$entry) {
            return false;
        }

        $entry->status = 'claimed';
        $entry->claimed_at = now();
        $entry->save();

        Log::info("Waitlist: {$email} claimed slot for {$date}");

        return true;
    }
}
