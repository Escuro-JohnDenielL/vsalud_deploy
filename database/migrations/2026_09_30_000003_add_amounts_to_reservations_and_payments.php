<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->decimal('total_amount', 10, 2)->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        Schema::table('reservation', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
};
