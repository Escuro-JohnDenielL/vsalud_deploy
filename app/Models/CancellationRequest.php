<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationRequest extends Model
{
    protected $table = 'cancellation_requests';

    protected $fillable = [
        'reserve_id',
        'patron_email',
        'reason',
        'status',
        'admin_id',
        'admin_note',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reserve_id', 'reserve_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }
}
