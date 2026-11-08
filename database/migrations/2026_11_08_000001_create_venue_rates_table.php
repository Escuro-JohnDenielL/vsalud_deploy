<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('venue_rates')) {
            Schema::create('venue_rates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('venue_id');
                $table->string('label', 100);
                $table->string('period', 4)->nullable();
                $table->decimal('price', 10, 2);
                $table->timestamps();

                $table->index('venue_id');
                $table->foreign('venue_id')->references('id')->on('venues')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('venues')) {
            return;
        }

        foreach (config('venue_pricing.defaults', []) as $venueName => $rates) {
            $venue = DB::table('venues')->where('name', $venueName)->first(['id']);
            if (! $venue) {
                continue;
            }

            foreach ($rates as $rate) {
                $query = DB::table('venue_rates')
                    ->where('venue_id', $venue->id)
                    ->where('label', $rate['label']);

                if ($rate['period'] === null) {
                    $query->whereNull('period');
                } else {
                    $query->where('period', $rate['period']);
                }

                if (! $query->exists()) {
                    DB::table('venue_rates')->insert([
                        'venue_id' => $venue->id,
                        'label' => $rate['label'],
                        'period' => $rate['period'],
                        'price' => $rate['price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_rates');
    }
};
