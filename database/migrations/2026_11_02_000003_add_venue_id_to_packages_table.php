<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A package is held in exactly one venue, so it stores a single venue_id.
 *
 * The column is nullable: existing packages have no venue yet and keep working
 * (the patron "View venue" button simply does not render). On delete the venue
 * reference is cleared rather than deleting the package; VenueController also
 * refuses to delete a venue that still has packages, so this is a safety net.
 *
 * Idempotent because the Railway database was imported from database/villatry.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        if (! Schema::hasColumn('packages', 'venue_id')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->unsignedInteger('venue_id')->nullable()->after('inclusions');
            });
        }

        $this->addIndex('packages', 'packages_venue_id_index', ['venue_id']);

        // Foreign keys need matching types and InnoDB. Guarded so a legacy
        // imported table can never block a deploy over a missing constraint.
        if (Schema::hasTable('venues')) {
            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->foreign('venue_id')
                        ->references('id')
                        ->on('venues')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // Index is already in place; integrity is enforced in the controller.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('packages') || ! Schema::hasColumn('packages', 'venue_id')) {
            return;
        }

        try {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropForeign(['venue_id']);
            });
        } catch (\Throwable $e) {
            // Constraint never existed.
        }

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('venue_id');
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))->pluck('Key_name')->unique();

        if ($existing->contains($name)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            $table,
            $name,
            implode(', ', array_map(fn (string $column) => "`{$column}`", $columns))
        ));
    }
};
