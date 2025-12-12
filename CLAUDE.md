# CLAUDE.md - Mens Circle Niederbayern Project Guide

This document provides Claude Code with essential context about the Mens Circle Niederbayern project (mens-circle.de) to ensure consistent, high-quality code contributions.

## Project Overview

**Type**: TYPO3 CMS Distribution / Full-Stack Web Application
**Purpose**: Community platform for Mens Circle Niederbayern to manage events, registrations, and newsletter subscriptions
**Primary Language**: PHP 8.5 (backend) + TypeScript (frontend)
**TYPO3 Version**: 13.3+ (with 14 support)

## Technology Stack

### Backend
- **Framework**: TYPO3 13.3+ with Extbase/Fluid
- **PHP**: 8.5 with strict types (`declare(strict_types=1)`)
- **Database**: MariaDB 11.8 / MySQL compatible
- **ORM**: Doctrine via TYPO3 Extbase
- **Caching**: Redis (production)
- **Email**: Symfony Mailer with Fluid templates
- **Queue**: Symfony Messenger with database transport
- **Security**: LCOBUCCI JWT, PHP Sodium
- **Monitoring**: Sentry for error tracking

### Frontend
- **Build Tool**: Vite 7 with vite-plugin-typo3
- **Runtime**: Bun >= 1.0 (Node 20+)
- **Language**: TypeScript with strict typing
- **Styling**: Lightning CSS (no PostCSS)
- **CSS Methodology**: BEM with logical properties
- **Animations**: GSAP 3.13, Motion 12.23
- **Components**: Custom web components via @beardcoder/simple-components
- **Calendar**: Leaflet 1.9.4, iCal export via eluceo/ical

### Development Environment
- **Local Dev**: DDEV (Docker-based)
- **Production Runtime**: FrankenPHP + Caddy + Docker
- **Process Manager**: Supervisor (for queue workers)

## Project Structure

```
mens-circle.de/
├── packages/
│   └── sitepackage/              # Main TYPO3 extension
│       ├── Classes/              # PHP domain logic
│       │   ├── Controller/       # Extbase controllers
│       │   ├── Domain/           # Models & Repositories
│       │   ├── Service/          # Business logic services
│       │   ├── Middleware/       # HTTP middleware
│       │   ├── Command/          # CLI tasks
│       │   ├── EventListener/    # TYPO3 event hooks
│       │   ├── ViewHelpers/      # Custom Fluid tags
│       │   └── Message/          # Symfony Messenger messages
│       ├── Configuration/        # TYPO3 config
│       │   ├── Sets/             # Site configuration bundles
│       │   ├── TCA/              # Database table configs
│       │   ├── FlexForm/         # Plugin configs
│       │   └── Routes/           # URL routing
│       ├── Resources/
│       │   ├── Private/
│       │   │   ├── Assets/       # Source CSS/TS
│       │   │   ├── Templates/    # Fluid templates
│       │   │   ├── Layouts/      # Master pages
│       │   │   ├── Partials/     # Template fragments
│       │   │   └── Components/   # Reusable components
│       │   └── Public/           # Static assets
│       └── Tests/                # Test suites
├── config/                       # System configuration
├── public/                       # Web root
├── .ddev/                        # DDEV config
├── .docker/                      # Production Docker
└── .github/                      # CI/CD workflows
```

## Code Style & Standards

### PHP

**Style Guide**: PSR-12 with PHP 8.5 features

**Required Patterns**:
- Always use `declare(strict_types=1)` at the top of each file
- Type all properties, parameters, and return values
- Use nullable types (`?Type`) when appropriate
- Constructor property promotion for dependency injection
- Prefer readonly properties where applicable

**File Header**:
All PHP files must include this header comment (automatically added by PHP-CS-Fixer):
```php
<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "sitepackage" by Markus Sommer.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace MensCircle\Sitepackage\...;
```

**Namespace**: All classes use `MensCircle\Sitepackage\` (PSR-4)

**Examples**:
```php
<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Service;

use Psr\Log\LoggerInterface;

final class EmailService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
    ) {}

    public function sendEmail(string $recipient, string $subject): bool
    {
        // Implementation
    }
}
```

### TypeScript

**Style**: Single quotes, 2-space indent, no semicolons

**Patterns**:
- Factory pattern for component lifecycle
- Dispose pattern for cleanup
- Dynamic imports for code splitting
- Strict typing enabled

**Example**:
```typescript
import type { ComponentConfig } from './types'

export class EventCard {
  private element: HTMLElement

  constructor(element: HTMLElement, config: ComponentConfig) {
    this.element = element
    this.init()
  }

  private init(): void {
    // Implementation
  }

  dispose(): void {
    // Cleanup
  }
}
```

### CSS

**Methodology**: BEM with custom suffixes

**Patterns**:
- Use CSS layers: `@layer reset, base, layout, components, utilities;`
- Prefer logical properties (`inline-size` over `width`, `block-start` over `top`)
- Alphabetical property ordering
- 4-space indentation

**Naming Convention**:
```css
/* Standard BEM */
.EventCard {}
.EventCard__title {}
.EventCard--featured {}

/* Combined modifiers */
.combined-EventCard-featured {}
```

**Example**:
```css
@layer components {
  .EventCard {
    background-color: var(--color-surface);
    border-radius: var(--radius-md);
    inline-size: 100%;
    padding-block: var(--space-4);
    padding-inline: var(--space-3);
  }

  .EventCard__title {
    color: var(--color-text-primary);
    font-size: var(--text-xl);
  }
}
```

## Architecture Patterns

### Backend (Extbase MVC)

**Flow**: Controller → Service → Repository → Model

**Dependency Injection**: Constructor-based via TYPO3's PSR-11 container
```php
public function __construct(
    private readonly EventRepository $eventRepository,
    private readonly EmailService $emailService,
) {}
```

**Repository Pattern**:
- Extend `\TYPO3\CMS\Extbase\Persistence\Repository`
- Use `StoragePageAgnosticTrait` for cross-page queries
- Custom query methods in repositories

**Service Layer**:
- Place business logic in Services, not Controllers
- Services are stateless and reusable
- Example services: `EmailService`, `TokenService`, `DoubleOptInService`

### Frontend (Component-Based)

**Component Lifecycle**:
```typescript
// Factory creates and manages components
ComponentFactory.create('EventCard', element, config)

// Components implement dispose for cleanup
component.dispose()
```

**Code Splitting**:
```typescript
// Heavy features loaded dynamically
const { initParallax } = await import('./features/parallax')
```

### Async Processing

**Symfony Messenger** for background tasks:
```php
// Dispatch message
$this->messageBus->dispatch(new SendEmailMessage($email));

// Handler processes asynchronously
final class SendEmailMessageHandler implements MessageHandlerInterface
{
    public function __invoke(SendEmailMessage $message): void
    {
        // Process email
    }
}
```

## Key Features & Implementation Notes

### Event Management
- Events are Extbase domain models with rich relationships
- Custom slug-based routing for SEO-friendly URLs
- Cached feed endpoints (JSON, iCal, JCal) with ETags
- Schema.org structured data for events

### Newsletter Subscriptions
- Double opt-in flow with secure JWT tokens
- Automatic TYPO3 frontend user creation
- Token-based unsubscribe mechanism
- Email templates in Fluid

### Caching Strategy
- Redis for production
- Feed middleware implements ETag validation
- TYPO3 cache framework for page/content caching

### Security
- CSRF protection via TYPO3 forms
- JWT tokens for subscription verification
- PHP Sodium for encryption
- All user input validated and sanitized

## Development Workflow

### Local Development
```bash
# Start DDEV environment
ddev start

# Install dependencies
ddev composer install
ddev bun install

# Build assets
ddev bun run build        # Production build
ddev bun run dev          # Development with hot reload

# Access site
ddev launch
```

### Code Quality Tools

**PHP**:
- `composer run phpstan` - Static analysis (level 7)
- `composer run fix-php` - PHP-CS-Fixer
- `composer run rector` - Automated modernization
- GrumPHP runs checks pre-commit

**JavaScript/TypeScript**:
- `bun run lint` - ESLint checks
- `bun run lint:fix` - Auto-fix
- `bun run format` - Prettier

**CSS**:
- `bun run stylelint` - CSS linting
- Validates BEM, logical properties, alphabetical ordering

### Testing
```bash
# Run tests
composer test

# Test suites in Tests/Unit/ and Tests/Performance/
```

### Git Workflow

**Branches**:
- `main` - Production branch
- `develop` - Development branch (current)
- Feature branches from `develop`

**Commit Messages**: Follow Conventional Commits
```
feat: add event registration confirmation email
fix: correct timezone handling in event display
refactor: simplify subscription token validation
docs: update API documentation
```

### Deployment

**GitHub Actions** handles deployment:
1. Build assets with Vite
2. Multi-stage Docker build
3. Deploy to production server via SSH
4. Run database migrations
5. Clear TYPO3 caches

## Important Conventions

### File Organization
- Controllers handle HTTP requests only, delegate to Services
- Services contain reusable business logic
- Repositories handle data access
- ViewHelpers for Fluid template extensions
- Middleware for request/response processing

### Database Tables
- Custom tables defined in `Configuration/TCA/`
- Use Extbase property mapping conventions
- Follow TYPO3 TCA structure

### Routing
- Event routes: `/veranstaltungen/{slug}`
- API routes defined in `Configuration/Routes/`
- Feed endpoints: `/events/feed.json`, `/events/feed.ics`

### Email Templates
- Fluid templates in `Resources/Private/Templates/Email/`
- Use `EmailService` for sending
- Support HTML and plain text versions

## Quality Standards

### Code Quality Targets
- **PHPStan**: Level 7 (strict)
- **Type Coverage**: 100% for new code
- **ESLint**: No errors, minimal warnings
- **Accessibility**: WCAG 2.1 AA minimum

### Performance
- Lazy load images with `data-src`
- Code splitting for heavy features
- Redis caching in production
- Optimized Vite builds

### Browser Support
- Modern browsers (ES2020+)
- Progressive enhancement approach
- View Transitions API for supported browsers

## Common Tasks

### Adding a New Event Field
1. Add property to `Domain/Model/Event.php`
2. Update TCA in `Configuration/TCA/tx_sitepackage_domain_model_event.php`
3. Run `typo3 database:updateschema`
4. Update Fluid templates if displaying

### Creating a New Service
1. Create in `Classes/Service/`
2. Add header comment
3. Use constructor injection
4. Make stateless and reusable
5. Add type hints everywhere

### Adding a Frontend Component
1. Create TypeScript class in `Resources/Private/Assets/Scripts/`
2. Register in factory
3. Implement `dispose()` method
4. Add corresponding CSS in `Resources/Private/Assets/Styles/`

### Adding a CLI Command
1. Create in `Classes/Command/`
2. Extend `\Symfony\Component\Console\Command\Command`
3. Register in `Configuration/Services.yaml`
4. Schedule via TYPO3 Scheduler if needed

## Troubleshooting

### Cache Issues
```bash
ddev typo3 cache:flush
```

### Asset Build Issues
```bash
ddev bun run clean
ddev bun install
ddev bun run build
```

### Database Schema Out of Sync
```bash
ddev typo3 database:updateschema
```

## Resources

- **TYPO3 Documentation**: https://docs.typo3.org/
- **Extbase Guide**: https://docs.typo3.org/m/typo3/book-extbasefluid/
- **Vite Plugin**: https://github.com/s2b/vite-plugin-typo3
- **Lightning CSS**: https://lightningcss.dev/

## Notes for AI Assistants

- Always read existing code before making changes
- Follow established patterns in the codebase
- Use dependency injection, never instantiate services directly
- Maintain strict typing in both PHP and TypeScript
- Add proper error handling and logging
- Test locally with DDEV before committing
- Keep commits atomic and well-described
- Never skip code quality checks
- Prioritize accessibility and performance
- When in doubt, ask before making architectural changes

## Project-Specific Preferences

- **No Over-Engineering**: Keep solutions simple and focused
- **No Unnecessary Abstractions**: Don't create helpers for one-time operations
- **Security First**: Validate at system boundaries, use TYPO3's security features
- **Accessibility**: Always consider keyboard navigation and screen readers
- **Progressive Enhancement**: Core functionality must work without JavaScript
- **Semantic HTML**: Use appropriate HTML5 elements
- **CSS-First**: Prefer CSS solutions over JavaScript when possible
