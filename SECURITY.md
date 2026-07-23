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

*Policy last reviewed: July 2026*
