<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add nullable inquiry_id column
        Schema::table('payments', function (Blueprint $table) {
            $table->integer('inquiry_id')->nullable()->after('reservation_code');
        });

        // Step 2: Backfill existing records — match reservation_code to inquiry.tracking_code
        // Use COLLATE to handle different column collations between tables
        DB::statement("
            UPDATE payments p
            JOIN inquiry i ON p.reservation_code = i.tracking_code COLLATE utf8mb4_unicode_ci
            SET p.inquiry_id = i.inquiry_id
        ");

        // Step 3: Add foreign key constraint
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('inquiry_id')
                ->references('inquiry_id')
                ->on('inquiry')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['inquiry_id']);
            $table->dropColumn('inquiry_id');
        });
    }
};
