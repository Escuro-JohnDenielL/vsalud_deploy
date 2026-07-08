<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add form_data JSON column to inquiry table
        Schema::table('inquiry', function (Blueprint $table) {
            $table->json('form_data')->nullable()->after('status');
        });

        // Add form_data JSON column to reservation table
        Schema::table('reservation', function (Blueprint $table) {
            $table->json('form_data')->nullable()->after('status');
        });

        // Convert ENUM columns to VARCHAR for future flexibility
        // These columns are still written to for backward compatibility,
        // but dynamic forms may introduce new values not in the ENUM list.
        DB::statement("ALTER TABLE inquiry MODIFY venue VARCHAR(255) NULL");
        DB::statement("ALTER TABLE inquiry MODIFY event_type VARCHAR(255) NULL");
        DB::statement("ALTER TABLE inquiry MODIFY theme_motif VARCHAR(255) NULL");
    }

    public function down(): void
    {
        Schema::table('inquiry', function (Blueprint $table) {
            $table->dropColumn('form_data');
        });

        Schema::table('reservation', function (Blueprint $table) {
            $table->dropColumn('form_data');
        });

        // Restore ENUMs (only if rolling back)
        DB::statement("ALTER TABLE inquiry MODIFY venue ENUM('Villa I', 'Villa II', 'Private Pool', 'Elizabeth Hall', 'Others') NULL");
        DB::statement("ALTER TABLE inquiry MODIFY event_type ENUM('Baptismal Package', 'Birthday Package', 'Standard Package', 'Kiddie Package', 'Debut Package', 'Wedding Package', 'Others') NULL");
        DB::statement("ALTER TABLE inquiry MODIFY theme_motif ENUM('Floral', 'Rustic', 'Beach', 'Modern', 'Elegant', 'Others') NULL");
    }
};
