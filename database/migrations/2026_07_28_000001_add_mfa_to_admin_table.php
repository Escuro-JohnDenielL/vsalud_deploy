<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->text('mfa_secret')->nullable()->after('password_reset_code_expires_at');
            $table->string('mfa_method', 20)->nullable()->after('mfa_secret');
            $table->timestamp('mfa_setup_completed_at')->nullable()->after('mfa_method');
            $table->string('last_login_ip', 45)->nullable()->after('mfa_setup_completed_at');
            $table->timestamp('last_login_at')->nullable()->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropColumn([
                'mfa_secret',
                'mfa_method',
                'mfa_setup_completed_at',
                'last_login_ip',
                'last_login_at',
            ]);
        });
    }
};
