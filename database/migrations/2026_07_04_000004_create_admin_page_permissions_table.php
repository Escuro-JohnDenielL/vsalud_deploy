<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Available page slugs (for reference — not enforced at DB level):
     *   packages, reservations, inquiries, reserve-logs, reports, feedback
     *
     * Pages NOT in permissions (always accessible):
     *   profile  — every admin sees their own profile
     *   it-management  — super_admin only, gated by role
     */
    public function up(): void
    {
        Schema::create('admin_page_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('admin_id');
            $table->foreign('admin_id')
                ->references('admin_id')
                ->on('admin')
                ->cascadeOnDelete();
            $table->string('page_slug'); // e.g. 'packages', 'inquiries', 'reports'
            $table->timestamps();

            $table->unique(['admin_id', 'page_slug']);
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_page_permissions');
    }
};
