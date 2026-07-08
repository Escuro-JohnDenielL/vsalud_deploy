<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('field_type'); // text, textarea, email, tel, select, checkbox, radio, date, time
            $table->string('label');
            $table->string('name'); // machine name (snake_case)
            $table->string('placeholder')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->json('options')->nullable(); // for select/radio/checkbox
            $table->json('validation_rules')->nullable(); // e.g. ["required","string","max:255"]
            $table->boolean('has_other_option')->default(false); // adds "Others" + text input
            $table->boolean('is_fixed')->default(false); // cannot be deleted/renamed
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
