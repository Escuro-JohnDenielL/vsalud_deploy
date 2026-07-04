<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PaymentSetting extends Model
{
    protected $table = 'payment_settings';

    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /**
     * Get a payment setting value by key, with optional default.
     * Results are cached in memory for the duration of the request.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        static $loaded = null;

        if ($loaded === null) {
            $loaded = Cache::remember('payment_settings', 3600, function () {
                return static::pluck('value', 'key')->all();
            });
        }

        return $loaded[$key] ?? $default;
    }

    /**
     * Clear the cached settings (call this after updating).
     */
    public static function clearCache(): void
    {
        Cache::forget('payment_settings');
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }
}
