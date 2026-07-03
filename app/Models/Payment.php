<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'full_name',
        'payment_type',
        'payment_method',
        'tracking_code',
        'reservation_code',
        'receipt_path',
        'email',
    ];
}
