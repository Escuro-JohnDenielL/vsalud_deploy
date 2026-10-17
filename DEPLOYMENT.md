# Deployment notes — Villa Salud

This file lists only configuration **names** and manual steps. It contains no
secrets and no values. Read it before pointing a fresh environment at the app.

## Environment variable names

### Application

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_DEBUG`
- `APP_URL`
- `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`
- `MFA_SHOW_CODE_DEV` — must stay false in production. It only takes effect when `APP_DEBUG=true`.

### Database

- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### Mail — the delivery path the whole patron journey depends on

- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

If `MAIL_MAILER` is unset the mailer falls back to `log`: messages are written
to the log instead of delivered, and the UI still reports success.

### Cache, session, queue

- `CACHE_STORE` — use `database` or `redis` in production. The `file` store works
  on a single instance but is lost on redeploy, and its `Cache::add` is not
  atomic across processes.
- `SESSION_DRIVER` — `file` sessions are lost on redeploy; `database` keeps admins signed in.
- `SESSION_LIFETIME`
- `QUEUE_CONNECTION`

### File storage

- `FILESYSTEM_DISK`
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`
- `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT`

### Business rules

- `PAYMENT_DOWNPAYMENT_PERCENT` — share of the reservation total that a "half" receipt represents (default 25).
- `AVAILABILITY_MAX_EVENTS_PER_DAY` — per-day event cap behind the calendar states (default 4).

### AI assistant

- `GEMINI_API_KEY`, `GEMINI_MODEL`

### Footer and contact details

- None. Contact, social and venue details are literal values in `config/villasalud.php`
  (`contact.*`, `social.*`, `venues`). There are no `VILLASALUD_*` environment
  variables. Edit the config file and redeploy to change them.

## What the deploy does NOT automate

- **Scheduler.** `Procfile`, `railway.json` and `Docker` start only the web server.
  Nothing runs `php artisan schedule:work`, so `routes/console.php` schedules are
  not executed by a worker.
  - Waitlist expiry is swept lazily from `/calendar/availability`.
  - `app:send-event-reminders` and `app:send-payment-reminders` are attempted at
    most once per hour from `/calendar/availability` and the admin dashboard,
    guarded by `Cache::add` and run after the response is sent.
  - A real scheduler process is still required for reliable daily mail.
- **Backups.** `backup:database` exists but is not scheduled anywhere.
- **Seeding.** No deploy step runs `db:seed`.
- **Migrations.** The start command runs a hardcoded list of migration paths, not
  `migrate --force`. Migrations not named there (including the three
  `2026_09_30_*` migrations) will not run automatically.

## Fresh database — manual steps

1. Apply every migration:
   `php artisan migrate --force`
2. Create the first super admin (values are placeholders, use real ones locally):
   `php artisan tinker`
   then `App\Models\Admin::create(['f_name' => '<first-name>', 'l_name' => '<last-name>', 'email' => '<admin-email>', 'password' => Illuminate\Support\Facades\Hash::make('<password>'), 'role' => 'super_admin']);`
3. Publish the reservation form:
   `php artisan db:seed --class=ReservationFormSeeder`
   Without it the patron reservation form answers "currently unavailable".
4. Seed payment settings:
   `php artisan db:seed --class=PaymentSettingsSeeder`
   or fill them in the admin Payment Settings page. The patron payment page has no
   hardcoded fallback.
5. In the admin Payment Settings page, upload the GCash and bank QR images if the
   deployment needs them.
6. Make sure `php artisan storage:link` has run once (the start command attempts it).
7. Sign in as the super admin, complete MFA setup, then create any other admin
   accounts and page permissions through IT Management.

## Verification after a deploy

- Sign in, complete MFA, and open Packages, Inquiries, Reservation Logs and Reports.
- Submit a patron inquiry and confirm the tracking code appears once on screen and
  arrives by email.
- Upload a receipt, approve it, and confirm the patron email is sent.
- Cancel an inquiry and confirm the patron email is sent.
- Load `/calendar/availability` and the admin dashboard without errors.
- Confirm the response headers `X-Content-Type-Options`, `X-Frame-Options` and
  `Referrer-Policy` are present.
