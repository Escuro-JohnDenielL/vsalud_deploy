<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'investigating', 'resolved', 'closed'])->default('open');
            $table->dateTime('detected_at');
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            $table->string('reported_by', 100)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('severity');
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
