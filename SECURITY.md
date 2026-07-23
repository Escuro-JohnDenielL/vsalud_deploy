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

## Reporting a Security Issue or System Incident

If you discover a security vulnerability, suspect a breach, or need to report a system issue affecting Villa Salud:

- **Email**: `security@villasalud.com` — monitored by the system administrator
- **Response time**: Within 48 hours on business days
- **What to include**: A brief description of the issue, how it was discovered, and any relevant system logs or timestamps.

For urgent incidents (e.g., active data breach, service outage), contact the administrator directly via phone or messaging in addition to email.

All reported issues are logged in the **Incident Log** (accessible from the admin Settings menu) and tracked until resolution.

---

*Policy last reviewed: July 2026*
