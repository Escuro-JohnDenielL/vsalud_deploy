<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->integer('admin_id', true);
            $table->string('email', 255);
            $table->string('f_name', 255);
            $table->string('l_name', 255);
            $table->string('password', 255);
            $table->string('phone', 15);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('profile_picture', 255)->default('default.png');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
