<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes used by the booking/availability lookups.
 *
 * The Railway database was imported from database/villatry.sql, whose schema
 * has no index on reservation.status + reservation.date and no index at all on
 * patron.email. Every "is this date full?", calendar load and patron lookup
 * was therefore a full table scan.
 *
 * Idempotent on purpose: the SQL import and older migration runs may already
 * have produced some of these indexes.
 */
return new class extends Migration
{
    /** @var array<string, array<string, array<int, string>>> */
    private array $indexes = [
        'reservation' => [
            'reservation_status_date_index' => ['status', 'date'],
        ],
        'patron' => [
            'patron_email_index' => ['email'],
        ],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->indexes as $table => $definitions) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->unique();

            foreach ($definitions as $name => $columns) {
                if ($existing->contains($name)) {
                    continue;
                }

                DB::statement(sprintf(
                    'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
                    $table,
                    $name,
                    implode(', ', array_map(fn (string $column) => "`{$column}`", $columns))
                ));
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->indexes as $table => $definitions) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                ->pluck('Key_name')
                ->unique();

            foreach (array_keys($definitions) as $name) {
                if (! $existing->contains($name)) {
                    continue;
                }

                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
            }
        }
    }
};
