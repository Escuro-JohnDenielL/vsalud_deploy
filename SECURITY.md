# Security & Patch Management Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Dependency Update Process

### Automated (Dependabot)

Dependabot is configured (`.github/dependabot.yml`) to open pull requests automatically for:

- **Composer dependencies** — checked weekly (every Monday), including Laravel core, Sanctum, and all PHP packages.
- **npm dependencies** — checked weekly (every Monday) for Vite and frontend packages.

Dependabot groups non-breaking minor/patch updates into single PRs to keep noise low. Security updates are prioritized by GitHub automatically.

### Cadence — "Patch Tuesday" (Every Other Tuesday)

1. **Review Dependabot PRs** — every other Tuesday, review and merge any open Dependabot PRs. Run test suite before merging.
2. **Manual `composer outdated` check** — before each deploy to Railway, optionally run:
   ```bash
   composer outdated --direct   # show only direct dependencies
   ```
   to catch anything Dependabot might have skipped (e.g., packages pinned by constraints).
3. **Deploy to Railway** — after merging dependency updates, deploy normally via Railway (the existing Docker build will handle `composer install --no-dev --optimize-autoloader`).

### Emergency Security Patches

For critical CVEs that can't wait for the next cadence:

1. Update the affected package locally: `composer update <package>`
2. Test thoroughly
3. Deploy immediately via Railway

### Responsibilities

- **Primary**: Lead developer reviews and merges Dependabot PRs.
- **Frequency**: Every 2 weeks (adjustable as the project matures).
- **Logging**: Dependency updates are tracked via Dependabot PR history and Railway deploy logs.

---

## Payment Receipt Storage and Access

Proof-of-payment uploads are handled as follows.

- **Storage location**: the Laravel `public` disk, which on Railway is the persistent volume mounted at `/var/www/storage/app/public`. Files are written to the `receipts/` directory. R2 and the private `local` disk remain as fallbacks for environments without a volume.
- **File names**: Laravel generates the name (`store()`), so each file is a random 40 character hexadecimal name. Names are not sequential and cannot be guessed.
- **Upload endpoint**: `POST /submit-payment` is public (patrons are not logged in). It is limited to JPG and PNG validated by real content type (`image` plus `mimetypes:image/jpeg,image/png`), capped at 5 MB, rate limited (`throttle:5,1`), and it must match a real `inquiry.tracking_code` together with the patron email on record.
- **Admin viewing**: receipts are read only through `GET /admin/receipts/{payment}` (`PaymentLogsController@showReceipt`), which is behind `auth:admin`, `auth.session`, `mfa` and `page.permission`, and streams the file through the Storage facade with `X-Content-Type-Options: nosniff`. The `/storage` URL is not used for viewing.
- **Known residual risk**: because the volume root equals the public disk root and `storage:link` runs at boot, a receipt is technically reachable at `/storage/receipts/<hash>.jpg` with no authentication if that exact URL leaks. Directory listing is not enabled, so files cannot be browsed, only fetched by exact name.

**Mitigation status**: accepted for the current deployment. A second Railway volume cannot be added, the mount is fixed at `/var/www/storage/app/public`, and storing receipts on R2 adds noticeable latency for uploads and admin viewing. The mitigation in force is the unguessable random filename plus admin-only viewing through the authenticated route.

**Future options** if a stronger guarantee is required: add a second volume mounted at `/var/www/storage/app/private` and store receipts on the `local` disk (private, persistent, fast), move receipts to R2 (private, persistent, higher latency), or encrypt receipt bytes at rest on the current volume and decrypt only in the admin route.

---

## Reporting a Security Issue or System Incident

If you discover a security vulnerability, suspect a breach, or need to report a system issue affecting Villa Salud:

- **Email**: `security@villasalud.com` — monitored by the system administrator
- **Response time**: Within 48 hours on business days
- **What to include**: A brief description of the issue, how it was discovered, and any relevant system logs or timestamps.

For urgent incidents (e.g., active data breach, service outage), contact the administrator directly via phone or messaging in addition to email.

All reported issues are logged in the **Incident Log** (accessible from the admin Settings menu) and tracked until resolution.

---

*Policy last reviewed: July 2026*
