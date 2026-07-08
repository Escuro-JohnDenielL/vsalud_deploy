<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the duplicate foreign keys (ibfk format), keeping the properly named ones (fk_ format)
        // reservation_ibfk_1 duplicates fk_res_inquiry (both on inquiry_id)
        // reservation_ibfk_2 duplicates fk_res_patron (both on patron_id)
        Schema::table('reservation', function ($table) {
            $table->dropForeign('reservation_ibfk_1');
            $table->dropForeign('reservation_ibfk_2');
        });
    }

    public function down(): void
    {
        // Restore the dropped foreign keys if rolled back
        Schema::table('reservation', function ($table) {
            $table->foreign('inquiry_id', 'reservation_ibfk_1')
                  ->references('inquiry_id')->on('inquiry');
            $table->foreign('patron_id', 'reservation_ibfk_2')
                  ->references('patron_id')->on('patron');
        });
    }
};
