<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_settings')
            && ! DB::table('payment_settings')->where('key', 'bank_qr_path')->exists()) {
            DB::table('payment_settings')->insert([
                'key' => 'bank_qr_path',
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('payment_settings');
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_settings')) {
            DB::table('payment_settings')->where('key', 'bank_qr_path')->delete();
        }

        Cache::forget('payment_settings');
    }
};
