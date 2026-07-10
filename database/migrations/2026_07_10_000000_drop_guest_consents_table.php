<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('guest_consents');
    }

    public function down(): void
    {
        // The original creation migration is deleted, so we can't recreate it here.
        // If you need to roll back, restore the deleted migration files first.
    }
};
