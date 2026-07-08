<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist', function (Blueprint $table) {
            $table->id();
            $table->string('patron_name', 255);
            $table->string('patron_email', 255);
            $table->date('requested_date');
            $table->enum('status', ['waiting', 'notified', 'claimed', 'expired'])->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            // One entry per person per date
            $table->unique(['patron_email', 'requested_date']);
            // Index for efficient lookups by date + status
            $table->index(['requested_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist');
    }
};
