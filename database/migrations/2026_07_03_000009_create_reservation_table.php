<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation', function (Blueprint $table) {
            $table->integer('reserve_id', true);
            $table->integer('inquiry_id');
            $table->integer('patron_id');
            $table->integer('admin_id')->nullable();
            $table->enum('status', ['active', 'cancelled', 'completed'])->default('active');
            $table->date('date')->nullable();
            $table->string('time', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->text('message')->nullable();
            $table->string('venue', 255)->nullable();
            $table->string('event_type', 255)->nullable();
            $table->string('theme_motif', 255)->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Only the properly named foreign keys (avoiding duplicates)
            $table->foreign('inquiry_id')->references('inquiry_id')->on('inquiry');
            $table->foreign('patron_id')->references('patron_id')->on('patron');
            $table->foreign('admin_id')->references('admin_id')->on('admin');

            $table->index('admin_id');
            $table->index('patron_id');
            $table->index('inquiry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation');
    }
};
