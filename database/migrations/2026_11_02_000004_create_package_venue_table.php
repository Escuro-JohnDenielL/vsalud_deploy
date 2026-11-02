<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A package can be held in more than one venue (e.g. Villa I or Villa II), so
 * the single packages.venue_id column becomes a package_venue pivot.
 *
 * Existing links are copied into the pivot before the column is dropped.
 * Both steps are guarded so a partly-migrated database can re-run this safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('packages') && Schema::hasTable('venues') && ! Schema::hasTable('package_venue')) {
            Schema::create('package_venue', function (Blueprint $table) {
                $table->unsignedInteger('package_id');
                $table->unsignedInteger('venue_id');

                $table->primary(['package_id', 'venue_id']);

                try {
                    $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
                    $table->foreign('venue_id')->references('id')->on('venues')->cascadeOnDelete();
                } catch (\Throwable $e) {
                    // Index-only fallback for imported tables that refuse FKs.
                }
            });
        }

        // Backfill from the old single-venue column.
        if (Schema::hasTable('package_venue') && Schema::hasColumn('packages', 'venue_id')) {
            DB::table('packages')
                ->whereNotNull('venue_id')
                ->orderBy('id')
                ->get(['id', 'venue_id'])
                ->each(function ($row) {
                    DB::table('package_venue')->insertOrIgnore([
                        'package_id' => $row->id,
                        'venue_id' => $row->venue_id,
                    ]);
                });

            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->dropForeign(['venue_id']);
                });
            } catch (\Throwable $e) {
                // No FK to drop.
            }

            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->dropColumn('venue_id');
                });
            } catch (\Throwable $e) {
                // Column already gone.
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('packages') && ! Schema::hasColumn('packages', 'venue_id')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->unsignedInteger('venue_id')->nullable()->after('inclusions');
            });

            if (Schema::hasTable('package_venue')) {
                DB::table('package_venue')
                    ->orderBy('package_id')
                    ->get()
                    ->groupBy('package_id')
                    ->each(function ($rows, $packageId) {
                        DB::table('packages')
                            ->where('id', $packageId)
                            ->update(['venue_id' => $rows->first()->venue_id]);
                    });
            }
        }

        Schema::dropIfExists('package_venue');
    }
};
