# Mens Circle Niederbayern Website

TYPO3 CMS distribution that powers [mens-circle.de](https://mens-circle.de), the online home of the Mens Circle community in Niederbayern. The project focuses on providing a welcoming space for men to discover upcoming circles, register for events, and stay connected through a double opt-in newsletter.

## What makes this project special

- **Event experience tuned for conversions** – detail pages include rich schema.org metadata, dynamic hero blocks, and per-event iCal downloads that work well on iOS and Android.
- **Calendar feeds everywhere** – `/events/feed.json`, `/events/feed.ics`, and `/events/feed.jcal` provide cached machine-readable data, while `/api/event/{id}/ical` generates on-demand calendar files with intelligent reminders.
- **Newsletter with double opt-in** – visitors subscribe through a custom form that creates TYPO3 frontend users, sends transactional emails, and supports secure unsubscribe tokens.
- **Crafted frontend** – built with Vite, TypeScript, and Bun, using GSAP animations and logical CSS, the design mirrors the mindful tone of the physical circle.
- **Operational tooling** – a DDEV setup for local development, Sentry integration hooks, and a FrankenPHP-based Docker image for production streamline operations.

## Tech stack

| Area | Tools |
| --- | --- |
| CMS | TYPO3 13, custom `mens-circle/sitepackage` extension |
| PHP | PHP 8.4, Composer, TYPO3 Console |
| Frontend | Vite 7, Bun, TypeScript, Lightning CSS, GSAP |
| Storage | MariaDB (via DDEV) |
| Delivery | FrankenPHP runtime, Docker, Caddy |

## Prerequisites

- Docker Desktop (for the recommended DDEV workflow)
- [DDEV](https://ddev.readthedocs.io/en/stable/)
- Composer 2
- Bun >= 1.0 (Node.js >= 20 if you prefer npm/pnpm)

Optional but useful:

- Mailpit (already bundled with DDEV) for previewing outbound mail
- A valid `JWT_SECRET` in `.env` for newsletter token generation

## Getting started with DDEV

```bash
# Start the containers and project network
ddev start

# Install PHP dependencies
ddev composer install

# Install frontend packages using Bun (configured via .ddev/config.vite.yaml)
ddev bun install

# Build frontend assets once (or run the dev server, see below)
ddev bun run vite build

# Open the site and TYPO3 backend
ddev launch
ddev launch /admin
```

The TYPO3 backend is available at `https://mens-circle.ddev.site/admin`. Default administrator credentials live in the team password manager – no hardcoded defaults are shipped with the project.

### Live-reload frontend development

The Vite dev server is proxied through DDEV:

```bash
ddev vite dev
```

Once running, visit `https://vite.mens-circle.ddev.site` for instant feedback while editing assets in `packages/sitepackage/Resources/Private`.

### Database and imports

- `ddev import-db --src=path/to/dump.sql` restores a production snapshot.
- Generated files and caches live in `var/` and `public/typo3temp/`; these directories are mounted as DDEV writable volumes.

## Running the project without DDEV

1. Ensure PHP 8.4 with required extensions (pdo_mysql, intl, gd, redis, zip, sodium, apcu).
2. Configure a web server (Apache, Nginx, or Caddy) with document root `public/`.
3. Copy `.env.example` (if provided) to `.env` and set `TYPO3_CONTEXT` and `JWT_SECRET`.
4. Execute:

   ```bash
   composer install
   bun install
   bun run vite build   # or bun vite for development mode
   ```

5. Run TYPO3 setup via `vendor/bin/typo3 setup` or `composer exec typo3 setup` and provide database credentials.

## Application architecture highlights

- `packages/sitepackage/Classes/Controller/EventController.php` renders event listings, provides detail pages, and redirects to the next upcoming circle.
- `packages/sitepackage/Classes/Middleware/EventFeedMiddleware.php` and `EventApiMiddleware.php` serve cached feeds and per-event calendar responses with smart ETag headers.
- `packages/sitepackage/Classes/Service` contains reusable services such as `EmailService`, `EventCalendarService`, `FrontendUserService`, and `TokenService`.
- `packages/sitepackage/Configuration/Sets/*` bundles TYPO3 site configuration (routes, TypoScript, newsletter settings) for reuse.
- Frontend markup lives in `packages/sitepackage/Resources/Private/PageView`, with Vite entry points in the neighbouring `Templates` directory.

## Useful TYPO3 CLI commands

```bash
# Run extension setup tasks (scheduler, database migrations, etc.)
ddev composer run run-extension-setup

# Update translations and apply available upgrades
ddev composer run run-upgrade

# Flush caches, helpful after configuration changes
ddev typo3 cache:flush
```

## Outbound email

- Transactional emails use Fluid templates in `Resources/Private/Templates/Emails/`.
- In DDEV, messages are routed through Mailpit (`ddev launch mailpit`).
- Production mail delivery is configured via TYPO3 mail settings in `config/system/settings.php`.

## APIs & integrations

- **Event feed**: `https://mens-circle.de/events/feed.{json|ics|jcal}`
- **Per-event iCal**: `https://mens-circle.de/api/event/{uid}/ical`
- **Newsletter subscription**: POST forms handled by `SubscriptionController`, including double opt-in token flow.
- **Structured data**: Schema.org Event data is rendered on detail pages to improve search visibility.

## Deployment

The included `Dockerfile` builds on `dunglas/frankenphp:1-php8.4` and bundles Composer/Bun assets for production use:

```bash
docker build -t mens-circle:latest .
docker run --rm -p 8080:80 mens-circle:latest
```

Customize `config/Caddyfile` and environment variables to match the target infrastructure.

## Maintenance checklist

- Keep Composer and Bun dependencies updated (`ddev composer outdated`, `ddev bun update`).
- Review Sentry logs (configured via environment DSN) for regressions.
- Ensure cron or TYPO3 Scheduler jobs run regularly for newsletter and event notifications.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for full details.

