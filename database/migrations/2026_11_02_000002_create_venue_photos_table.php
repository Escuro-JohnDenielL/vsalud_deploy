<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per uploaded villa photo. This is the single place villa pictures
 * live — packages never store their own copy, they only reference the venue.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venue_photos')) {
            return;
        }

        Schema::create('venue_photos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venue_id');
            $table->string('path', 255);
            $table->string('alt_text', 160)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['venue_id', 'sort_order']);
            $table->foreign('venue_id')
                ->references('id')
                ->on('venues')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_photos');
    }
};
