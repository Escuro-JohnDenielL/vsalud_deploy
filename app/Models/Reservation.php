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
    ];

    public function patron()
    {
        return $this->belongsTo(\App\Models\Patron::class, 'patron_id', 'patron_id');
    }
}
