<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->integer('payment_id', true);
            $table->string('full_name', 255);
            $table->string('payment_type', 50);
            $table->string('payment_method', 50);
            $table->string('tracking_code', 50)->unique();
            $table->string('reservation_code', 255);
            $table->string('receipt_path', 255);
            $table->string('email', 255);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
