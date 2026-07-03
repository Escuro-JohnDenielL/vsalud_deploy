<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry', function (Blueprint $table) {
            $table->integer('inquiry_id', true);
            $table->enum('created_by_type', ['admin', 'patron'])->default('patron');
            $table->integer('admin_id')->nullable();
            $table->string('time', 50)->nullable();
            $table->integer('patron_id')->nullable()->index('fk_inquiry_patron');
            $table->string('tracking_code', 20)->nullable()->unique();
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
            $table->date('date');
            $table->enum('venue', ['Villa I', 'Villa II', 'Private Pool', 'Elizabeth Hall', 'Others'])->nullable();
            $table->enum('event_type', ['Baptismal Package', 'Birthday Package', 'Standard Package', 'Kiddie Package', 'Debut Package', 'Wedding Package', 'Others'])->nullable();
            $table->enum('theme_motif', ['Floral', 'Rustic', 'Beach', 'Modern', 'Elegant', 'Others'])->nullable();
            $table->string('other_event_type', 255)->nullable();
            $table->string('other_theme_motif', 255)->nullable();
            $table->string('other_venue', 255)->nullable();
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Cancelled'])->default('Pending');
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('created_by_type', 'idx_inquiry_creator_type');
            $table->index('admin_id', 'idx_inquiry_admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry');
    }
};
