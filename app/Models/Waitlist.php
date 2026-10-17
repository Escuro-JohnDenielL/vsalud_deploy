<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    protected $table = 'waitlist';

    protected $fillable = [
        'patron_name',
        'patron_email',
        'requested_date',
        'status',
        'notified_at',
        'claimed_at',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'notified_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    /**
     * Store only the calendar date.
     *
     * The column is a DATE, so MySQL truncates automatically, but SQLite keeps
     * the whole string. Writing "Y-m-d" keeps the unique per-day constraint and
     * the date-scoped lookups working on both.
     */
    public function setRequestedDateAttribute($value): void
    {
        $this->attributes['requested_date'] = $value === null
            ? null
            : \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('requested_date', $date);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the oldest waiting entry for a given date (FCFS).
     *
     * Ordered by creation time and then by id: two entries created inside the
     * same second still come out in the order they were inserted, so "first
     * come, first served" is stable instead of depending on storage order.
     */
    public static function nextWaitingForDate($date)
    {
        return static::forDate($date)
            ->byStatus('waiting')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Get all entries that have been notified over 24 hours ago.
     */
    public static function expiredNotifications()
    {
        return static::byStatus('notified')
            ->where('notified_at', '<', now()->subHours(24))
            ->get();
    }
}
