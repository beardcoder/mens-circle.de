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
ddev bun install

# Start Vite dev server
ddev bun run dev

# Build for production
ddev bun run build
```

## Project Structure

```
├── config/             # TYPO3 configuration
├── packages/
│   └── sitepackage/    # Main extension
│       ├── Classes/    # PHP classes
│       ├── Configuration/
│       └── Resources/
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
ddev bun run stylelint  # Stylelint
```

## AJAX Forms

Forms can be submitted via AJAX using the `data-ajax-form` attribute:

```html
<form data-ajax-form="/api/form/contact">
  <input type="text" name="name" required>
  <input type="email" name="email" required>
  <textarea name="message" required></textarea>
  <button type="submit">Send</button>
</form>
```

Available endpoints:
- `/api/form/contact` - Contact form
- `/api/form/newsletter` - Newsletter subscription
