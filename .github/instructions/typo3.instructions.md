---
description: 'GitHub Copilot Custom Instructions for TYPO3 v13 LTS & PHP 8.2/8.4'
applyTo: '**'
---

# GitHub Copilot Custom Instructions for TYPO3 v13 LTS & PHP 8.2/8.4

These instructions guide Copilot to generate code that aligns with the **official TYPO3 v13 LTS documentation** (https://docs.typo3.org), modern PHP 8.2/8.4 features, TYPO3 best practices, and industry standards to ensure quality, maintainability, and security.

## ✅ General Coding Standards

- Follow **PSR-12** coding style and structure.
- Always use `declare(strict_types=1);` in PHP files.
- Prefer short, expressive, and readable code.
- Use **meaningful, descriptive names** for variables, functions, classes, and files.
- Add PHPDoc blocks for classes, methods, and complex logic.
- Organize code into small, reusable components with a **single responsibility**.
- Avoid magic numbers or hard-coded strings; use TYPO3 configuration, constants, or language labels.

## ✅ PHP 8.2/8.4 Best Practices

- Use **readonly properties** for immutability where applicable.
- Prefer **Enums** over string or integer constants.
- Utilize **Constructor Property Promotion**.
- Use **Union Types**, **Intersection Types**, and **true/false return types** for strict typing.
- Apply **Static Return Type** where needed.
- Use the **Nullsafe Operator (?->)** for optional chaining.
- Use **Named Arguments** for clarity in function calls.
- Mark classes as **final** if not intended for extension.

## ✅ TYPO3 Project Structure & Conventions

- Follow TYPO3’s recommended directory and configuration structure:
    - `Classes/Controller` – Extbase controllers
    - `Classes/Domain/Model` – Domain models
    - `Classes/Domain/Repository` – Repositories
    - `Classes/ViewHelpers` – Fluid ViewHelpers
    - `Configuration/TCA` – Table Configuration Array files
    - `Configuration/Services.yaml` – Service definitions & DI
    - `Configuration/TypoScript` – TypoScript setup & constants
    - `Resources/Private` – Templates, Partials, Layouts, Language files
    - `Resources/Public` – Public assets (CSS, JS, Images)

- Controllers must:
    - Be thin and delegate business logic to services or repositories.
    - Use **Dependency Injection** (DI) instead of `$GLOBALS`.
    - Return **typed responses** (e.g., `HtmlResponse`, `JsonResponse`).
    - Use Fluid templates for rendering HTML output.

- TCA definitions:
    - Follow official syntax from TYPO3 v13 docs.
    - Use `renderType` where applicable instead of deprecated types.
    - Avoid legacy wizards and prefer modern FormEngine features.

## ✅ TYPO3 Core APIs & Services

- Use modern TYPO3 APIs:
    - **PSR-14 Events** for hooks and extensions instead of legacy signals/slots.
    - **Context API** for accessing environment-dependent data.
    - **Site Handling & Routing API** for multilingual setups and route enhancers.
    - **Caching Framework** for performance optimization.
    - **Logging Framework** with PSR-3 compatibility.

- Never use deprecated global variables like `$GLOBALS['TSFE']` or `$GLOBALS['TYPO3_DB']`; use dependency-injected services instead.

## ✅ TypoScript & TSconfig

- Separate **constants** and **setup** into dedicated files.
- Use **Page TSconfig** for backend UI configuration.
- Use **User TSconfig** for user/group-specific settings.
- Avoid inline TypoScript in PHP or templates; load it via `ext_localconf.php` or `Configuration/TypoScript`.

## ✅ Security Best Practices

- Always enable **CSRF protection** in forms.
- Use **prepared statements** or QueryBuilder for database access.
- Escape all output in Fluid templates (`{variable -> f:format.htmlspecialchars()}` by default).
- Store sensitive information in `.env` or TYPO3 configuration files, not in code.
- Apply **Access Control Checks** for backend modules and frontend controllers.
- Follow **least privilege principle** for backend user roles.

## ✅ Testing Standards

- Use **PHPUnit** for unit and functional tests.
- For frontend rendering, use TYPO3’s **FunctionalTestCase**.
- Use fixtures and TYPO3 test utilities for consistent setup.
- Write feature tests for extension functionality.
- Mock external services where possible.

## ✅ Software Quality & Maintainability

- Follow **SOLID**, **DRY**, and **KISS** principles.
- Document complex logic with PHPDoc and inline comments.
- Default to **immutability** and **dependency injection**.
- Avoid inline SQL; prefer TYPO3’s QueryBuilder or Repository methods.
- Keep extensions small, modular, and reusable.

## ✅ Performance & Optimization

- Use **caching** for data and template fragments.
- Minimize database queries by eager loading related records in Extbase.
- Optimize TypoScript for fewer includes and minimal parsing overhead.
- Use **AssetCollector API** instead of manually including CSS/JS.
- Avoid loading large datasets into memory unnecessarily.

## ✅ Additional Copilot Behavior Preferences

- Always reference **the latest TYPO3 v13 LTS documentation** for API usage and examples.
- Suggest complete, runnable code snippets, including required registration files (`ext_localconf.php`, `Services.yaml`, TCA files).
- Provide inline comments where behavior may not be obvious.
- Avoid outdated or deprecated TYPO3 APIs and patterns.
- Use **clear, professional language** without unnecessary exclamations.
