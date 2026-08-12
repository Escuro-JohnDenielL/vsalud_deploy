<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensures the MFA/2FA columns exist on the admin table and that the
     * admin_trusted_devices table exists. Idempotent (safe to run on any
     * environment) — Railway's DB was imported from an SQL dump that predates
     * these columns, which caused a 500 when saving MFA fields during setup.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin')) {
            Schema::table('admin', function (Blueprint $table) {
                if (!Schema::hasColumn('admin', 'mfa_secret')) {
                    $table->text('mfa_secret')->nullable();
                }
                if (!Schema::hasColumn('admin', 'mfa_method')) {
                    $table->string('mfa_method', 20)->nullable();
                }
                if (!Schema::hasColumn('admin', 'mfa_setup_completed_at')) {
                    $table->timestamp('mfa_setup_completed_at')->nullable();
                }
                if (!Schema::hasColumn('admin', 'mfa_verified_at')) {
                    $table->timestamp('mfa_verified_at')->nullable();
                }
                if (!Schema::hasColumn('admin', 'last_login_ip')) {
                    $table->string('last_login_ip', 45)->nullable();
                }
                if (!Schema::hasColumn('admin', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable();
                }
            });
        }

        if (!Schema::hasTable('admin_trusted_devices')) {
            Schema::create('admin_trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->integer('admin_id');
                $table->string('device_token', 64)->unique();
                $table->string('device_name', 255)->nullable();
                $table->string('ip_address', 45);
                $table->text('user_agent')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->index('admin_id');
                $table->index('device_token');
            });
        }
    }

    public function down(): void
    {
        // Intentionally a no-op — the columns/table are owned by their original migrations.
    }
};
