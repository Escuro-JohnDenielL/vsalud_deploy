<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\Inquiry;
use App\Support\IdentityMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\WaitlistSlotAvailable;
use App\Mail\WaitlistSlotExpired;
use Illuminate\Support\Facades\Log;

class WaitlistService
{
    /** Cache key guarding the lazy expiry sweep. */
    public const EXPIRY_GUARD_KEY = 'waitlist:expiry-sweep';

    /** How long that guard holds the sweep back, in seconds. */
    public const EXPIRY_GUARD_SECONDS = 300;

    /**
     * Normalized form of a waitlist email: trimmed and lowercased.
     *
     * Every lookup goes through this, so an entry added as "Maria@Example.com "
     * is found again as "maria@example.com" and the same patron cannot hold two
     * places on one date.
     */
    public static function normalizeEmail(?string $email): string
    {
        return IdentityMatch::normalizeEmail($email);
    }
    /**
     * Notify the next person on the waitlist for a given date.
     * Called when a slot frees up (e.g., cancellation approved).
     *
     * @param string $date The date (Y-m-d) that now has a free slot.
     * @return array{notified: bool, mail_sent: bool} Whether someone was
     *         notified, and whether the notification email was actually sent.
     *         mail_sent stays true when nobody was waiting (no send attempted).
     */
    public function notifyNextForDate(string $date): array
    {
        // One offer at a time. While somebody holds an unexpired offer for this
        // date, nobody else may be offered the same slot.
        $alreadyOffered = Waitlist::forDate($date)->where('status', 'notified')->exists();

        if ($alreadyOffered) {
            return ['notified' => false, 'mail_sent' => true];
        }

        $next = Waitlist::nextWaitingForDate($date);

        if (!$next) {
            return ['notified' => false, 'mail_sent' => true]; // No one waiting
        }

        // Atomic flip: only the caller that actually moves the row from
        // "waiting" to "notified" sends the mail, so two requests arriving
        // together cannot offer the same slot twice.
        $claimed = Waitlist::whereKey($next->getKey())
            ->where('status', 'waiting')
            ->update(['status' => 'notified', 'notified_at' => now()]);

        if ($claimed === 0) {
            return ['notified' => false, 'mail_sent' => true];
        }

        try {
            Mail::to($next->patron_email)->send(new WaitlistSlotAvailable([
                'name'           => $next->patron_name,
                'date'           => $next->requested_date->format('Y-m-d'),
                'hours'          => 24,
            ]));

            Log::info("Waitlist: Notified {$next->patron_email} for date {$date}");
            return ['notified' => true, 'mail_sent' => true];
        } catch (\Exception $e) {
            Log::error("Waitlist: Failed to notify {$next->patron_email} for date {$date}: " . $e->getMessage());
            return ['notified' => true, 'mail_sent' => false];
        }
    }

    /**
     * Check for expired notifications and rotate to the next person.
     * Called by the scheduled command.
     *
     * Safe to run twice: each entry is only retired by the run that flips it
     * from "notified" to "expired", so a repeat or a concurrent run does not
     * send a second expiry notice or skip the patron behind it.
     *
     * @return int Number of expirations processed.
     */
    public function processExpiredNotifications(): int
    {
        $expired = Waitlist::expiredNotifications();
        $count = 0;

        foreach ($expired as $entry) {
            $date = $entry->requested_date->format('Y-m-d');

            // Mark as expired, atomically and only once.
            $expiredNow = Waitlist::whereKey($entry->getKey())
                ->where('status', 'notified')
                ->update(['status' => 'expired']);

            if ($expiredNow === 0) {
                continue; // Another run already retired this entry.
            }

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
     * Run the expiry sweep at most once every five minutes.
     *
     * The scheduler that would run this every minute (`schedule:work`) is not
     * started by the deploy scripts, so the sweep is also driven lazily from
     * the pages that would show a stale offer. The cache guard keeps that to
     * one sweep per window no matter how much traffic arrives.
     *
     * @return int Number of expirations processed (0 when the guard held).
     */
    public function sweepExpiredNotifications(): int
    {
        if (! Cache::add(self::EXPIRY_GUARD_KEY, now()->getTimestamp(), self::EXPIRY_GUARD_SECONDS)) {
            return 0;
        }

        try {
            return $this->processExpiredNotifications();
        } catch (\Throwable $e) {
            Log::error('Waitlist: expiry sweep failed: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Check if a date is currently full (per the availability system).
     *
     * A date is full when it holds at least the configured daily cap of active
     * reservations, or when an admin marked it Closed. Pending inquiries do not
     * count toward the cap.
     *
     * @param string $date Y-m-d format
     * @return bool
     */
    public function isDateFull(string $date): bool
    {
        $availability = app(\App\Services\AvailabilityService::class);

        if ($availability->isClosed($date)) {
            return true;
        }

        return $availability->isAtCapacity($date);
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
        // Normalize once, and use the same rule for the duplicate check and the
        // stored value, so the same patron cannot join twice on one date by
        // changing the case or the surrounding space of their address.
        $email = self::normalizeEmail($email);

        $existing = $this->existingEntryFor($email, (string) $date);

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
                $existing->patron_email = $email;
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
        $entry = $this->matchEmail(
                Waitlist::forDate($date)->where('status', 'notified'),
                self::normalizeEmail($email)
            )->first();

        if (!$entry) {
            return false;
        }

        // Atomic: only the caller that flips "notified" to "claimed" wins.
        $claimed = Waitlist::whereKey($entry->getKey())
            ->where('status', 'notified')
            ->update(['status' => 'claimed', 'claimed_at' => now()]);

        if ($claimed === 0) {
            return false;
        }

        Log::info("Waitlist: {$email} claimed slot for {$date}");

        return true;
    }

    /**
     * The stored entry for an email on a date, compared after trimming and
     * lowercasing so entries written before normalization are still found.
     */
    private function existingEntryFor(string $normalizedEmail, string $date): ?Waitlist
    {
        return $this->matchEmail(Waitlist::forDate($date), $normalizedEmail)->first();
    }

    /**
     * Add the normalized-email comparison to a waitlist query.
     */
    private function matchEmail($query, string $normalizedEmail)
    {
        return $query->whereRaw('LOWER(TRIM(patron_email)) = ?', [$normalizedEmail]);
    }
}
