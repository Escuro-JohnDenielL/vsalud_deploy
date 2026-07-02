<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_consents', function (Blueprint $table) {
            $table->string('label')->nullable()->after('guest_token');
            $table->string('email')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('guest_consents', function (Blueprint $table) {
            $table->dropColumn(['label', 'email']);
        });
    }
};
