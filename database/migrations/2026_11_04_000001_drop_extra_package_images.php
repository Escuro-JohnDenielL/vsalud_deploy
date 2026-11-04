<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A package now carries a single photo, so the extra gallery columns are gone.
 *
 * Idempotent because the Railway database was imported from database/villatry.sql.
 * The files already uploaded for image 2/3 stay on disk; only the references go.
 */
return new class extends Migration
{
    private const COLUMNS = ['image_2_path', 'image_3_path'];

    public function up(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        $columns = array_values(array_filter(
            self::COLUMNS,
            fn (string $column) => Schema::hasColumn('packages', $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('packages', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        if (! Schema::hasColumn('packages', 'image_2_path')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->string('image_2_path', 255)->nullable()->after('image_path');
            });
        }

        if (! Schema::hasColumn('packages', 'image_3_path')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->string('image_3_path', 255)->nullable()->after('image_2_path');
            });
        }
    }
};
