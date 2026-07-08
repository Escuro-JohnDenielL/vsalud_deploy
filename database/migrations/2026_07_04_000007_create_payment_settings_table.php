<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed with current hardcoded values
        $settings = [
            // GCash
            ['key' => 'gcash_account_name', 'value' => 'Elizabeth R.'],
            ['key' => 'gcash_mobile_number', 'value' => '09062236120'],
            ['key' => 'gcash_qr_path', 'value' => 'images/gcash.jpg'],

            // Bank Transfer (BPI)
            ['key' => 'bank_name', 'value' => 'BPI Savings Bank'],
            ['key' => 'bank_account_name', 'value' => 'Ernesto Rafael Jr. and/or Elizabeth Rafael'],
            ['key' => 'bank_account_number', 'value' => '8230001538'],

            // Cash
            ['key' => 'cash_instructions', 'value' => 'Pay in person at the Villa office. Provide your tracking code at the counter.'],

            // Contact
            ['key' => 'contact_phone', 'value' => '(+63) 912 345 6789'],
            ['key' => 'contact_email', 'value' => 'villasalud.events@gmail.com'],
        ];

        DB::table('payment_settings')->insert($settings);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
