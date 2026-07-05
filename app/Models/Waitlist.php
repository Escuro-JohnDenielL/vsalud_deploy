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
     */
    public static function nextWaitingForDate($date)
    {
        return static::forDate($date)
            ->byStatus('waiting')
            ->orderBy('created_at', 'asc')
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
