<?php

use App\Models\ActivityLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrades `activity_log` into a full admin audit trail.
 *
 * Added columns:
 *   category    – auth | security | data | config | system (what the Audit Logs page filters by)
 *   route_name  – the Laravel route that produced the entry
 *   http_method – POST / PUT / PATCH / DELETE
 *   subject     – short human label of the affected record, e.g. "Package #4"
 *
 * Rows written before this migration only have `activity_type`, so the migration
 * backfills `category` for them and ActivityLog::categoryFor() keeps deriving it
 * for any row that somehow arrives without one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return; // nothing to extend — the table is created by 2026_07_03_000001
        }

        Schema::table('activity_log', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_log', 'category')) {
                $table->string('category', 50)->nullable()->after('activity_type')->index();
            }
            if (! Schema::hasColumn('activity_log', 'route_name')) {
                $table->string('route_name', 150)->nullable()->after('category');
            }
            if (! Schema::hasColumn('activity_log', 'http_method')) {
                $table->string('http_method', 10)->nullable()->after('route_name');
            }
            if (! Schema::hasColumn('activity_log', 'subject')) {
                $table->string('subject', 191)->nullable()->after('http_method');
            }
        });

        $this->backfillCategories();
    }

    public function down(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            foreach (['subject', 'http_method', 'route_name', 'category'] as $column) {
                if (Schema::hasColumn('activity_log', $column)) {
                    if ($column === 'category') {
                        $table->dropIndex(['category']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Give existing history a category so the filter works for old entries too.
     * Best effort: on failure the page still derives a category at render time.
     */
    private function backfillCategories(): void
    {
        try {
            ActivityLog::query()
                ->whereNull('category')
                ->orderBy('log_id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        $row->timestamps = false; // don't rewrite updated_at
                        $row->category = ActivityLog::categoryFor($row->activity_type, $row->route_name);
                        $row->saveQuietly();
                    }
                });
        } catch (\Throwable $e) {
            report($e);
        }
    }
};
