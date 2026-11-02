<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookable venues (Villa I, Villa II, ...).
 *
 * A package points at one venue; the venue owns its photo gallery. That way
 * the same villa photos are uploaded once and reused by every package that
 * uses that venue, instead of being copied into each package row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venues')) {
            return;
        }

        Schema::create('venues', function (Blueprint $table) {
            // Integer id (not bigint) to match the legacy `packages` table, so
            // packages.venue_id can be a plain foreign key on both databases.
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
