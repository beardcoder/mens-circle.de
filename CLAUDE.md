# CLAUDE.md - Project Context for Claude Code

## Project Overview

This is a TYPO3 v14 website for **mens-circle.de**, a men's circle community platform. The project uses modern TYPO3 patterns with Fluid Components and a custom sitepackage.

## Tech Stack

- **CMS**: TYPO3 v14 (latest)
- **PHP**: 8.4+
- **Frontend**: Vanilla CSS with OKLCH color system, minimal JavaScript
- **Development**: DDEV
- **Production**: Docker with serversideup/php, deployed via Coolify

## Project Structure

```
├── config/
│   ├── sites/            # Site configuration (routing, languages)
│   └── system/           # TYPO3 system config
│       └── additional.php  # Environment-based configuration
├── packages/
│   └── sitepackage/      # Main extension
│       ├── Classes/
│       │   ├── Controller/   # Backend/Frontend controllers
│       │   ├── Domain/       # Entities, Repositories
│       │   └── Utility/      # Helper classes
│       ├── Configuration/
│       │   ├── FlexForms/    # Content element configurations
│       │   ├── TCA/          # Table Configuration Array
│       │   └── TypoScript/   # TypoScript setup
│       ├── Resources/
│       │   ├── Private/
│       │   │   ├── Components/   # Fluid Components
│       │   │   ├── Layouts/      # Fluid Layouts
│       │   │   ├── PageView/     # PAGEVIEW templates (TYPO3 v14)
│       │   │   ├── Partials/     # Fluid Partials
│       │   │   └── Templates/    # Controller templates
│       │   └── Public/
│       │       ├── Css/
│       │       └── JavaScript/
│       └── helpers.php       # Global helper functions
├── public/               # Web root
├── var/                  # Cache, logs, sessions
├── Dockerfile           # Production Docker image
└── docker/              # Docker configurations
```

## Key Conventions

### TYPO3 v14 Specifics

1. **PAGEVIEW Content Object**: Templates are in `Resources/Private/PageView/` (not `Resources/Private/Templates/`)
2. **Config location**: `config/system/additional.php` (not `typo3conf/AdditionalConfiguration.php`)
3. **Site config**: YAML files in `config/sites/{site-identifier}/`

### Content Elements

Content elements use the prefix `mc_` (mens circle):
- `mc_hero` - Hero sections
- `mc_intro` - Introduction blocks
- `mc_cta` - Call to action
- `mc_faq` - FAQ accordion
- `mc_journey` - Journey/process steps
- `mc_testimonials` - Testimonials grid
- `mc_moderator` - Moderator profiles
- `mc_newsletter` - Newsletter signup

Each content element has:
- FlexForm in `Configuration/FlexForms/mc_*.xml`
- TCA registration in `Configuration/TCA/Overrides/tt_content.php`
- Template in `Resources/Private/PageView/Content/Mc*.html`

### CSS Design System

Uses OKLCH color space with CSS custom properties:

```css
--color-primary: oklch(45% 0.15 250);      /* Deep blue */
--color-secondary: oklch(55% 0.12 180);    /* Teal */
--color-accent: oklch(75% 0.15 85);        /* Warm gold */
--color-earth: oklch(35% 0.08 60);         /* Earth brown */
```

### Helper Functions

Global helpers available via `helpers.php`:
- `env($key, $default)` - Get environment variable
- `is_production()` / `is_development()` - Context check
- `app_url()` - Get APP_URL
- `dd(...$vars)` - Dump and die (dev only)

### Environment Variables

Key environment variables (see `.env.coolify` for full list):
- `TYPO3_CONTEXT` - Development/Production
- `APP_URL` - Base URL
- `DB_*` - Database connection
- `MAIL_*` - SMTP configuration
- `REDIS_*` - Redis caching (optional)

## Development Commands

```bash
# Start DDEV
ddev start

# Composer install
ddev composer install

# Clear cache
ddev typo3 cache:flush

# Database operations
ddev typo3 database:updateschema
```

## Coding Standards

- PSR-12 for PHP code
- Strict types in all PHP files
- German locale (de_DE.UTF-8)
- Use Fluid Components for reusable UI elements
- Prefer CSS custom properties over hardcoded values
- No external CSS frameworks - vanilla CSS only

## Newsletter System

Custom newsletter implementation with:
- Double opt-in confirmation
- Styled HTML email templates (Fluid)
- Subscription management via tokens
- API endpoints at `/newsletter/*` and `/api/newsletter/*`

## Event Management

Custom event management system with backend module:

### Domain Models
- `Event` - Event with title, slug, description, image, date/time, location, address, max_participants, cost_basis
- `EventRegistration` - Attendee registration with name, email, phone, status

### Backend Module
Located in the "Mens Circle" menu:
- **Dashboard** - Overview with stats (total events, upcoming, registrations)
- **Event List** - All events with publish toggle, edit, view actions
- **Event Detail** - Registration list, progress bar, CSV export

### Database Tables
- `tx_sitepackage_domain_model_event`
- `tx_sitepackage_domain_model_eventregistration`

### Key Features
- Unique constraint: one email per event
- Status tracking: pending, confirmed, cancelled
- iCal export generation
- Available spots calculation

## Docker Deployment

Uses `serversideup/php:8.5-fpm-nginx-alpine` with:
- Multi-stage build (composer, node, production)
- Built-in PHP/nginx optimization via environment variables
- Custom nginx config for TYPO3 routing only
- Coolify-ready with `.env.coolify` template
