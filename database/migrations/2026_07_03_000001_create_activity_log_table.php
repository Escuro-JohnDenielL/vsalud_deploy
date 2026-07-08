<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->string('log_id')->primary();
            $table->integer('admin_id');
            $table->string('activity_type', 50);
            $table->text('description')->nullable();
            $table->integer('inquiry_id')->nullable();
            $table->integer('reserve_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->index('admin_id');
            $table->index('activity_type');
            $table->index('created_at');
            $table->index('inquiry_id');
            $table->index('reserve_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
