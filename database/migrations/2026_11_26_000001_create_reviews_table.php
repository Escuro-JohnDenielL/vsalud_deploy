<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->integer('inquiry_id')->unique();
            $table->string('display_name', 100);
            $table->unsignedTinyInteger('rating');
            $table->text('message');
            $table->boolean('is_approved')->default(false)->index();
            $table->timestamps();

            $table->foreign('inquiry_id')
                ->references('inquiry_id')
                ->on('inquiry')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
