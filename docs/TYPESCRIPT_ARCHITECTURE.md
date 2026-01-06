# TypeScript Architecture - TYPO3 v14

This document describes the TypeScript architecture for the Männerkreis Straubing website, following TYPO3 v14 best practices.

## Overview

The TypeScript codebase is organized into modules that handle specific functionality:
- **Navigation**: Mobile menu and accessibility
- **Forms**: Newsletter, registration, and testimonial submissions
- **Scroll**: Animations, smooth scrolling, and sticky header
- **Calendar**: Event calendar integration
- **FAQ**: Accordion functionality

## Build System

### Vite + vite-plugin-typo3
The project uses Vite with the official TYPO3 plugin for modern asset building.

### Entry Points
Entry points are discovered automatically via `Configuration/ViteEntrypoints.json`:
```json
["../Resources/Private/**/*.entry.{ts,css}"]
```

Main entry: `Resources/Private/Assets/Scripts/app.entry.ts`

## Key Improvements in TYPO3 v14 Refactoring

### 1. Modern Browser APIs
- ✅ Replaced deprecated `window.pageYOffset` with `window.scrollY`
- ✅ IntersectionObserver for scroll animations
- ✅ Native `fetch()` API with proper error handling

### 2. Configuration Management
Created new `utils/config.ts` for centralized configuration:
- Prefers data attributes over window globals
- Backwards compatible with existing patterns
- CSRF token standardization

### 3. Enhanced Type Safety
- Strict TypeScript mode throughout
- Explicit return types on all functions
- No `any` types allowed
- Proper null checks

### 4. Better Code Organization
- Named functions instead of inline callbacks
- Comprehensive JSDoc documentation
- Early returns for cleaner code flow
- Error handling in app initialization

### 5. Accessibility Improvements
- ARIA attributes (role, aria-live, aria-expanded)
- Keyboard navigation (Escape, Enter)
- Focus management
- Semantic HTML in messages

## File Structure
```
Resources/Private/Assets/Scripts/
├── app.entry.ts          # Main entry point
├── modules/
│   ├── navigation.ts     # Mobile nav
│   ├── scrollHeader.ts   # Sticky header
│   ├── scrollAnimations.ts
│   ├── smoothScroll.ts
│   ├── faq.ts
│   ├── calendar.ts
│   └── forms/
│       ├── index.ts      # Forms entry
│       ├── newsletter.ts
│       ├── registration.ts
│       └── testimonial.ts
├── utils/
│   ├── validation.ts     # Form validation
│   ├── message.ts        # User messages
│   └── config.ts         # Configuration helpers (NEW)
└── types/
    └── index.ts          # TypeScript types
```

## Development Workflow

```bash
npm install        # Install dependencies
npm run dev        # Start Vite dev server
npm run lint       # Run ESLint
npm run lint:fix   # Fix ESLint issues
npm run format     # Format with Prettier
npm run build      # Build for production
```

## Migration Guide

### Data Attributes (Preferred)
```html
<!-- Old -->
<script>window.routes = { newsletter: '/api/newsletter' }</script>

<!-- New -->
<form data-api-newsletter="/api/newsletter">
<meta name="csrf-token" content="...">
```

### Modern APIs
```typescript
// Old
const scroll = window.pageYOffset

// New
const scroll = window.scrollY
```

## Browser Support
Targets modern browsers (ESNext):
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Resources
- [TYPO3 v14 Documentation](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/)
- [Vite Plugin TYPO3](https://github.com/s2b/vite-plugin-typo3)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
