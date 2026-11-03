<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds "Sabrina Hall" to the Venue choices of the published reservation form.
 *
 * That dropdown is seeded once by ReservationFormSeeder, so a database seeded
 * before the venue existed never picks up new options. This top-up is additive
 * and skips itself when the field is missing or the option is already there.
 */
return new class extends Migration
{
    private const VENUE = 'Sabrina Hall';

    public function up(): void
    {
        $this->toggleOption(true);
    }

    public function down(): void
    {
        $this->toggleOption(false);
    }

    private function toggleOption(bool $add): void
    {
        if (! Schema::hasTable('form_fields') || ! Schema::hasTable('forms')) {
            return;
        }

        $field = DB::table('form_fields')
            ->join('forms', 'forms.id', '=', 'form_fields.form_id')
            ->where('form_fields.name', 'venue')
            ->where('forms.slug', 'reservation')
            ->select('form_fields.id', 'form_fields.options')
            ->first();

        if (! $field) {
            return;
        }

        $decoded = json_decode((string) $field->options, true);
        $options = is_array($decoded) ? $decoded : [];

        // Older rows may store bare strings instead of {label, value} pairs.
        $options = array_values(array_map(function ($option) {
            return is_array($option)
                ? $option
                : ['label' => (string) $option, 'value' => (string) $option];
        }, $options));

        $index = null;
        foreach ($options as $position => $option) {
            if (mb_strtolower(trim((string) ($option['value'] ?? ''))) === mb_strtolower(self::VENUE)) {
                $index = $position;
                break;
            }
        }

        if ($add && $index === null) {
            $options[] = ['label' => self::VENUE, 'value' => self::VENUE];
        } elseif (! $add && $index !== null) {
            array_splice($options, $index, 1);
        } else {
            return;
        }

        DB::table('form_fields')->where('id', $field->id)->update([
            'options' => json_encode($options),
            'updated_at' => now(),
        ]);

        Cache::forget('venue_catalog:reservation');
    }
};
