<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservation';
    protected $primaryKey = 'reserve_id';

    protected $fillable = [
        'inquiry_id',
        'patron_id',
        'date',
        'time',
        'venue',
        'event_type',
        'theme_motif',
        'message',
        'status',
        'form_data',
        'event_reminder_sent_at',
        'payment_reminder_sent_at',
    ];

    protected $casts = [
        'form_data' => 'array',
    ];

    public function patron()
    {
        return $this->belongsTo(\App\Models\Patron::class, 'patron_id', 'patron_id');
    }

    public function inquiry()
    {
        return $this->belongsTo(\App\Models\Inquiry::class, 'inquiry_id', 'inquiry_id');
    }

    public function cancellationRequests()
    {
        return $this->hasMany(\App\Models\CancellationRequest::class, 'reserve_id', 'reserve_id');
    }
}
