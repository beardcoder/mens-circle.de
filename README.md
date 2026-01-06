# Mens Circle Website

TYPO3 v14 project for mens-circle.de

## Requirements

- PHP 8.4+
- Bun 1.2+ or Node 22+
- MariaDB 11.4+ or MySQL 8+
- DDEV (for local development)

## Local Development

```bash
# Start DDEV
ddev start

# Install dependencies
ddev composer install
ddev bun install  # or npm install

# Start Vite dev server
ddev bun run dev  # or npm run dev

# Build for production
ddev bun run build  # or npm run build
```

## Documentation

- [TypeScript Architecture](docs/TYPESCRIPT_ARCHITECTURE.md) - TYPO3 v14 frontend patterns and best practices
- [CLAUDE.md](CLAUDE.md) - Project context for AI assistants

## Project Structure

```
├── config/             # TYPO3 configuration
├── docs/              # Documentation
├── packages/
│   └── sitepackage/    # Main extension
│       ├── Classes/    # PHP classes
│       ├── Configuration/
│       └── Resources/
│           ├── Private/
│           │   └── Assets/
│           │       ├── Scripts/  # TypeScript modules
│           │       └── Styles/   # CSS
│           └── Public/
├── public/             # Web root
└── var/                # Cache, logs
```

## Code Quality

```bash
# PHP
ddev composer lint      # Check code style
ddev composer fix       # Fix code style
ddev composer phpstan   # Static analysis

# TypeScript/CSS
ddev bun run lint       # ESLint
ddev bun run lint:fix   # Auto-fix ESLint
ddev bun run format     # Prettier format
ddev bun run stylelint  # Stylelint
```

## Frontend Stack

- **Build Tool**: Vite with vite-plugin-typo3
- **TypeScript**: Strict mode, ES modules
- **CSS**: Vanilla CSS with OKLCH colors, Lightning CSS
- **Linting**: ESLint (strict), Prettier, Stylelint

## Forms & API

Forms use modern patterns with data attributes:

```html
<!-- Newsletter form -->
<form id="newsletterForm" data-api-newsletter="/api/newsletter">
  <input type="email" name="email" required>
  <button type="submit">Subscribe</button>
</form>

<!-- CSRF token (meta tag) -->
<meta name="csrf-token" content="...">
```

Available endpoints:
- `/api/newsletter` - Newsletter subscription
- `/api/event/register` - Event registration
- `/api/testimonial` - Testimonial submission
