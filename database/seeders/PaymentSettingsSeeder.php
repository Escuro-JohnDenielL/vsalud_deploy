<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guarantees that the `payment_settings` rows exist on every deploy.
 *
 * These values used to be duplicated as literal fallbacks inside the views
 * (`payment_setting('gcash_mobile_number', '09062236120')` and friends), which
 * meant two sources of truth that could silently drift apart — and a patron
 * could be shown an account number that no longer matched the real setting.
 *
 * The rows are now seeded here and read exclusively from the database, so the
 * Payment Settings page is the single place an admin edits them.
 *
 * Inserts are skipped when a row already exists, so anything the admin changed
 * in the UI survives redeploys.
 */
class PaymentSettingsSeeder extends Seeder
{
    /**
     * Canonical defaults, keyed by `payment_settings.key`.
     */
    public const DEFAULTS = [
        // GCash
        'gcash_account_name'   => 'Elizabeth R.',
        'gcash_mobile_number'  => '09062236120',
        'gcash_qr_path'        => 'images/gcash.jpg',

        // Bank transfer (BPI)
        'bank_name'            => 'BPI Savings Bank',
        'bank_account_name'    => 'Ernesto Rafael Jr. and/or Elizabeth Rafael',
        'bank_account_number'  => '8230001538',

        // Cash
        'cash_instructions'    => 'Pay in person at the Villa office. Provide your tracking code at the counter.',

        // Contact
        'contact_phone'        => '(+63) 912 345 6789',
        'contact_email'        => 'villasalud.events@gmail.com',
    ];

    public function run(): void
    {
        // Never let this step break a deploy. `database/villatry.sql` does not
        // contain `payment_settings`, and the original
        // `2026_07_04_000007_create_payment_settings_table` migration is not part
        // of the deploy start command — so on a SQL-restored database the table
        // may legitimately be absent. Skip instead of throwing.
        if (! Schema::hasTable('payment_settings')) {
            $this->command?->warn(
                'payment_settings table not found — skipping seed. Run '
                . '`php artisan migrate --path=database/migrations/2026_07_04_000007_create_payment_settings_table.php` '
                . 'if the table is missing.'
            );

            return;
        }

        $now = now();
        $inserted = 0;

        foreach (self::DEFAULTS as $key => $value) {
            // Insert-only: never clobber a value the admin has edited.
            if (DB::table('payment_settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('payment_settings')->insert([
                'key'        => $key,
                'value'      => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $inserted++;
        }

        $this->command?->info("Payment settings: {$inserted} row(s) inserted, "
            . (count(self::DEFAULTS) - $inserted) . ' already present.');
    }
}
