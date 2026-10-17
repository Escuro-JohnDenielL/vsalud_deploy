<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Support\TrackingCode;

class Inquiry extends Model
{
    protected $table = 'inquiry';

    protected $primaryKey = 'inquiry_id';

    protected $fillable = [
        'patron_id',
        'admin_id',
        'created_by_type',
        'tracking_code',
        'event_type',
        'other_event_type',
        'theme_motif',
        'other_theme_motif',
        'venue',
        'date',
        'time',
        'other_venue',
        // 'event_date',
        // 'event_time',
        'message',
        'status',
        'form_data',
    ];

    protected $casts = [
        'form_data' => 'array',
        'date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate tracking code. Same "VS-XXXXXX" format as the codes
        // already in the table, but drawn from random_bytes so the value is not
        // derivable from the clock, and checked against existing rows first.
        static::creating(function ($inquiry) {
            if (empty($inquiry->tracking_code)) {
                $inquiry->tracking_code = TrackingCode::unique(
                    fn (string $code) => static::query()->where('tracking_code', $code)->exists()
                );
            }
        });

        // Activity log after creation
        static::created(function ($inquiry) {
            if ($inquiry->created_by_type === 'admin' && $inquiry->admin_id) {
                $admin = Admin::find($inquiry->admin_id);

                if ($admin) {
                    $adminFullName = $admin->f_name . ' ' . $admin->l_name;

                    ActivityLog::create([
                        'admin_id'      => $admin->admin_id,
                        'activity_type' => 'Created Inquiry',
                        'description'   => "Admin {$adminFullName} created inquiry #{$inquiry->inquiry_id}",
                        'inquiry_id'    => $inquiry->inquiry_id,
                    ]);
                }
            }
        });
    }

    public function patron()
    {
        return $this->belongsTo(Patron::class, 'patron_id', 'patron_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class, 'inquiry_id', 'inquiry_id');
    }

    /**
     * The reservation created when this inquiry was confirmed.
     */
    public function reservation()
    {
        return $this->hasOne(\App\Models\Reservation::class, 'inquiry_id', 'inquiry_id');
    }
}
