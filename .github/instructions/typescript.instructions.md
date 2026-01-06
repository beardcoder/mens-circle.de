---
description: 'Copilot Instructions for Modern JavaScript/TypeScript Projects'
applyTo: '**/*.js, **/*.mjs, **/*.cjs, **/*.ts, **/*.tsx, **/*.jsx'
---

# Copilot Instructions for Modern JavaScript/TypeScript Projects

## Project Overview

You are working on a modern JavaScript/TypeScript project that follows best practices for maintainable, type-safe, and performant code. Focus on creating clean, functional, and well-documented code that prioritizes developer experience and code quality.

## Code Style & Conventions

### TypeScript Standards

- **Strict TypeScript Configuration**: Use `@tsconfig/recommended` or equivalent strict configuration as baseline
- **Explicit Types**: Always define explicit interface/type definitions for public APIs
- **Generics**: Use generics for reusable components with clear default values
- **Readonly Properties**: Use `readonly` for immutable properties in interfaces
- **Type Safety**: Prefer type safety over convenience, avoid `any` unless absolutely necessary

### Naming Conventions

- **Files**: kebab-case (e.g., `createComponent.ts`, `useLocalStorage.ts`)
- **Functions/Variables**: camelCase
- **Interfaces/Types**: PascalCase with descriptive names
- **Constants**: SCREAMING_SNAKE_CASE for module-level constants
- **Exported Utils**: Name utility exports as `* as domUtils`, `* as stringUtils`
- **Private/Internal**: Prefix with underscore `_` for internal-only functions

### API Design Patterns

#### 1. Function Interfaces

```typescript
// Always define clear function signatures with proper return types
export interface ApiResponse<T> {
    readonly data: T;
    readonly status: number;
    readonly error?: string;
}

export type CallbackFunction<T, R = void> = (input: T) => R;
export type CleanupFunction = () => void;
```

#### 2. Hook Pattern (for React-like or utility hooks)

```typescript
// Hooks should always return cleanup functions when applicable
export function useHookName(...args: unknown[]): CleanupFunction {
    // Setup logic

    return () => {
        // Cleanup logic
    };
}
```

#### 3. Observable/Signal Pattern

```typescript
// For reactive programming patterns
export interface Observable<T> {
    value: T;
    subscribe(fn: (value: T) => void): () => void;
}
```

#### 4. Builder Pattern

```typescript
// For complex object construction
export class ConfigBuilder<T> {
    private config: Partial<T> = {};

    public set<K extends keyof T>(key: K, value: T[K]): this {
        this.config[key] = value;
        return this;
    }

    public build(): T {
        return this.config as T;
    }
}
```

### Error Handling

- **Defensive Programming**: Add try-catch blocks for critical operations
- **Graceful Degradation**: Functions should handle edge cases without breaking the application
- **Meaningful Errors**: Use descriptive error messages with context
- **Error Types**: Define custom error classes for different error categories

```typescript
// Example error handling pattern
export class ValidationError extends Error {
    constructor(
        message: string,
        public field?: string,
    ) {
        super(message);
        this.name = 'ValidationError';
    }
}

export function safeOperation<T>(operation: () => T, fallback: T): T {
    try {
        return operation();
    } catch (error) {
        console.error('Operation failed:', error);
        return fallback;
    }
}
```

### Import/Export Conventions

- **Path Aliases**: Use `@/` for imports from src directory when configured
- **File Extensions**: Include `.js` extension for internal imports (ESM compatibility)
- **Re-exports**: Use `export *` in `index.ts` for clean API exposure
- **Named Exports**: Prefer named exports over default exports for better tree-shaking
- **Barrel Exports**: Create index files to simplify import paths

```typescript
// Good import patterns
import type { ApiResponse } from '@/types/api.js';
import { validateInput, formatOutput } from '@/utils/validation.js';
import * as stringUtils from '@/utils/string.js';

// Good export patterns
export { validateInput, formatOutput };
export type { ApiResponse, ValidationOptions };
```

### Testing Standards

- **Test Framework**: Use modern testing frameworks (Vitest, Jest, or similar)
- **Test Environment**: Configure appropriate test environment (happy-dom, jsdom, or node)
- **Test Structure**: Follow consistent describe/it patterns
- **Mocking**: Use framework mocking capabilities for external dependencies
- **Type Safety**: Maintain type safety in tests, use type assertions when necessary

```typescript
// Example test structure
describe('FeatureName', () => {
    beforeEach(() => {
        // Setup for each test
        vi.clearAllMocks();
    });

    afterEach(() => {
        // Cleanup after each test
    });

    it('should describe expected behavior clearly', () => {
        // Arrange
        const input = createTestInput();

        // Act
        const result = functionUnderTest(input);

        // Assert
        expect(result).toEqual(expectedOutput);
    });

    it('should handle edge cases gracefully', () => {
        // Test edge cases and error conditions
    });
});
```

### Code Quality & Best Practices

#### Functional Programming

- **Pure Functions**: Prefer pure functions that don't cause side effects
- **Immutability**: Use `readonly` and immutable data structures
- **Function Composition**: Build complex functionality from simple functions
- **Higher-Order Functions**: Use functions that take or return other functions

#### Performance Considerations

- **Lazy Loading**: Implement lazy loading for heavy operations
- **Memoization**: Cache expensive computations when appropriate
- **Tree Shaking**: Structure code to support optimal tree shaking
- **Bundle Analysis**: Consider bundle size impact of dependencies

#### Security Best Practices

- **Input Validation**: Always validate and sanitize user inputs
- **XSS Prevention**: Escape or sanitize any user-generated content
- **Type Guards**: Use type guards for runtime type checking
- **Dependency Management**: Keep dependencies updated and audit regularly

### Documentation Standards

- **JSDoc**: Add JSDoc comments for all public APIs
- **Type-first Documentation**: TypeScript interfaces should be self-documenting
- **Code Examples**: Include usage examples in JSDoc where helpful
- **README**: Maintain comprehensive README with setup and usage instructions

````typescript
/**
 * Validates user input according to specified rules
 *
 * @param input - The input data to validate
 * @param rules - Validation rules to apply
 * @returns Validation result with any errors
 *
 * @example
 * ```typescript
 * const result = validateInput(userData, {
 *   email: { required: true, type: 'email' },
 *   age: { required: false, type: 'number', min: 0 }
 * });
 * ```
 */
export function validateInput<T>(input: T, rules: ValidationRules<T>): ValidationResult {
    // Implementation
}
````

### Modern JavaScript/TypeScript Features

- **ES2020+ Features**: Use modern JavaScript features when appropriate
- **Optional Chaining**: Use `?.` for safe property access
- **Nullish Coalescing**: Use `??` for default values
- **Template Literals**: Use template literals for string formatting
- **Destructuring**: Use destructuring for cleaner variable assignment
- **Async/Await**: Prefer async/await over Promise chains

```typescript
// Modern syntax examples
const config = {
    apiUrl: process.env.API_URL ?? 'http://localhost:3000',
    timeout: options?.timeout ?? 5000,
    retries: settings.retries ?? 3,
};

const { data, error } = await fetchUserData(userId);

const message = `User ${user.name} has ${user.posts?.length ?? 0} posts`;
```

### Linting & Formatting

- **ESLint**: Use ESLint with TypeScript, stylistic, and best practice rules
- **Prettier**: Use Prettier for consistent code formatting
- **Import Sorting**: Sort imports consistently (external, internal, relative)
- **No Dead Code**: Remove unused imports, variables, and functions
- **Consistent Style**: Maintain consistent indentation, quotes, and semicolons

## General Development Guidelines

### Code Reviews

- **Small PRs**: Keep pull requests focused and reviewable
- **Self-Review**: Review your own code before requesting review
- **Documentation**: Update documentation when changing public APIs
- **Tests**: Include tests for new functionality and bug fixes

### Version Control

- **Commit Messages**: Write clear, descriptive commit messages
- **Branch Naming**: Use descriptive branch names (feature/, fix/, refactor/)
- **Change History**: Maintain a changelog for notable changes

### Accessibility & Internationalization

- **Semantic HTML**: Use appropriate semantic elements when working with DOM
- **ARIA Labels**: Include ARIA labels for dynamic content
- **Keyboard Navigation**: Ensure keyboard accessibility
- **Internationalization**: Design APIs to support multiple languages

Follow these conventions consistently across all JavaScript/TypeScript code to ensure maintainability, readability, and team collaboration.
