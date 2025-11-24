![PHP Version](https://img.shields.io/badge/PHP-8.5-blue.svg)
![TYPO3 Version](https://img.shields.io/badge/TYPO3-13-orange.svg)
![License](https://img.shields.io/badge/License-GPL%202.0-green.svg)

# Mens Circle Niederbayern Website

TYPO3 CMS distribution that powers [mens-circle.de](https://mens-circle.de), the online home of the Mens Circle community in Niederbayern. The project focuses on providing a welcoming space for men to discover upcoming circles, register for events, and stay connected through a double opt-in newsletter.

## ✨ Key Features

### Event Management

- **Event Listings & Details**: Comprehensive event pages with rich metadata and schema.org structured data for better SEO
- **Registration System**: Secure event registration with participant management and email notifications
- **Calendar Integration**: Multiple feed formats (JSON, iCal, JCal) for seamless calendar integration
- **Per-Event iCal Downloads**: On-demand calendar files with intelligent reminders for iOS and Android
- **Dynamic Hero Blocks**: Engaging visual presentations for each event

### Newsletter System

- **Double Opt-In Subscriptions**: Secure subscription process with email verification
- **Frontend User Integration**: Automatic creation of TYPO3 frontend users upon subscription
- **Transactional Emails**: Professional email templates for confirmations and notifications
- **Secure Unsubscribe**: Token-based unsubscribe functionality for compliance

### Frontend Experience

- **Modern Tech Stack**: Built with Vite, TypeScript, and Bun for optimal performance
- **Animations**: GSAP-powered animations that reflect the mindful tone of the community
- **Responsive Design**: Logical CSS ensuring great experience across all devices
- **Accessibility**: Focus on inclusive design and WCAG compliance

### Operational Excellence

- **DDEV Development Environment**: Streamlined local development setup
- **Docker Deployment**: FrankenPHP-based production containerization
- **Monitoring**: Sentry integration for error tracking and performance monitoring
- **Caching**: Intelligent caching strategies for feeds and API responses

## 🛠️ Tech Stack

| Area            | Tools                                                   |
| --------------- | ------------------------------------------------------- |
| **CMS**         | TYPO3 13.3+, custom `mens-circle/sitepackage` extension |
| **Backend**     | PHP 8.5, Composer, TYPO3 Console, Redis, Sodium         |
| **Frontend**    | Vite 7, Bun, TypeScript, Lightning CSS, GSAP, Motion    |
| **Database**    | MariaDB (via DDEV), Doctrine ORM                        |
| **Deployment**  | FrankenPHP runtime, Docker, Caddy                       |
| **Monitoring**  | Sentry for error tracking                               |
| **Development** | DDEV, ESLint, Stylelint, Prettier, PHP-CS-Fixer         |

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

1. Ensure PHP 8.5 with required extensions (pdo_mysql, intl, gd, redis, zip, sodium, apcu).
2. Configure a web server (Apache, Nginx, or Caddy) with document root `public/`.
3. Copy `.env.example` (if provided) to `.env` and set `TYPO3_CONTEXT` and `JWT_SECRET`.
4. Execute:

    ```bash
    composer install
    bun install
    bun run vite build   # or bun vite for development mode
    ```

5. Run TYPO3 setup via `vendor/bin/typo3 setup` or `composer exec typo3 setup` and provide database credentials.

## 🧑‍💻 Development

### Code Quality

This project maintains high code quality standards:

```bash
# Run PHP code style fixes
ddev composer run fix:php

# Run frontend linting and formatting
ddev bun run format
ddev bun run stylelint

# Run full quality checks (via GrumPHP)
ddev composer run grumphp
```

### Testing

The project uses TYPO3's testing framework. Run tests with:

```bash
ddev composer run test
```

### Extension Structure

The `sitepackage` extension follows TYPO3 best practices:

- **Controllers**: Handle event listings, details, and newsletter subscriptions
- **Services**: Reusable business logic for emails, tokens, and user management
- **Middleware**: API endpoints for feeds and calendar downloads
- **Models**: Domain objects for events, participants, and subscriptions
- **ViewHelpers**: Custom Fluid view helpers for templates

## 🏗️ Application Architecture Highlights

### Backend Structure

- **`EventController.php`**: Manages event listings, detail pages, registration, and redirects to upcoming events
- **`SubscriptionController.php`**: Handles newsletter subscriptions with double opt-in flow
- **Middleware**: `EventFeedMiddleware` and `EventApiMiddleware` serve cached feeds and calendar downloads with ETag headers
- **Services**: Reusable components including `EmailService`, `EventCalendarService`, `FrontendUserService`, and `TokenService`
- **Models**: Domain entities for Events, Participants, Newsletters, and Subscriptions

### Configuration

- **`Configuration/Sets/`**: Modular TYPO3 configuration bundles for different features (Events, Newsletter, Base setup)
- **Routes**: Custom routing for events and API endpoints
- **TCA**: Table configuration for custom database tables
- **FlexForms**: Content element configurations for plugins

### Frontend Integration

- **Templates**: Fluid templates in `Resources/Private/Templates/`
- **Assets**: Vite-managed frontend assets with TypeScript and modern CSS
- **ViewHelpers**: Custom Fluid view helpers for enhanced templating

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

The included `Dockerfile` builds on `dunglas/frankenphp:1-php8.5` and bundles Composer/Bun assets for production use:

```bash
docker build -t mens-circle:latest .
docker run --rm -p 8080:80 mens-circle:latest
```

Customize `config/Caddyfile` and environment variables to match the target infrastructure.

## Maintenance checklist

- Keep Composer and Bun dependencies updated (`ddev composer outdated`, `ddev bun update`).
- Review Sentry logs (configured via environment DSN) for regressions.
- Ensure cron or TYPO3 Scheduler jobs run regularly for newsletter and event notifications.

## 🤝 Contributing

We welcome contributions to improve the Mens Circle website! Please follow these guidelines:

### Development Workflow

1. Fork the repository and create a feature branch
2. Follow the [TYPO3 coding standards](https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Appendix/TypescriptCodingStandards.html)
3. Run code quality checks: `ddev composer run grumphp`
4. Test your changes thoroughly
5. Submit a pull request with a clear description

### Code Style

- PHP: PSR-12 compliant (enforced via PHP-CS-Fixer)
- TypeScript/JavaScript: ESLint + Prettier configuration
- CSS: Stylelint with logical CSS support
- Commit messages: Follow [Conventional Commits](conventionalcommit.json)

### Reporting Issues

- Use GitHub Issues for bugs and feature requests
- Include TYPO3 version, PHP version, and steps to reproduce
- Check existing issues before creating new ones

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for full details.
