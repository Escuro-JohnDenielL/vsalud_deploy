<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('profile_picture');
            $table->softDeletes();
        });

        // Set it@villasalud.test as the sole super_admin
        DB::table('admin')
            ->where('email', 'it@villasalud.test')
            ->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropSoftDeletes();
        });
    }
};
