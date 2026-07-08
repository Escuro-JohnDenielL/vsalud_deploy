<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->timestamp('event_reminder_sent_at')->nullable()->after('updated_at');
            $table->timestamp('payment_reminder_sent_at')->nullable()->after('event_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->dropColumn(['event_reminder_sent_at', 'payment_reminder_sent_at']);
        });
    }
};
