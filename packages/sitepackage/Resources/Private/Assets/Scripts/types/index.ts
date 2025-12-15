/**
 * Configuration types for the application
 */
export interface AppConfig {
    navigation: NavigationConfig
    animations: AnimationConfig
    development: boolean
}

export interface NavigationConfig {
    scrollThreshold: number
    hideOnScroll: boolean
}

export interface AnimationConfig {
    reducedMotion: boolean
}

/**
 * Form notification types
 */
export type NotificationType = 'success' | 'error' | 'info'

export interface NotificationOptions {
    message: string
    type: NotificationType
    duration?: number
}

/**
 * Intersection Observer options
 */
export interface ObserverOptions {
    root?: Element | null
    threshold?: number | number[]
    rootMargin?: string
}

/**
 * Utility function types
 */
export type DebouncedFunction<T extends (...args: any[]) => any> = (...args: Parameters<T>) => void

export type ThrottledFunction<T extends (...args: any[]) => any> = (...args: Parameters<T>) => void

/**
 * Factory Pattern Types (ES2023+ with Explicit Resource Management)
 * All components implement Disposable for automatic cleanup with 'using' keyword
 */

// Base factory interface with Symbol.dispose for automatic resource management
export interface Factory extends Disposable {
    [Symbol.dispose](): void
}

// Alias destroy to Symbol.dispose for backwards compatibility
export interface FactoryWithDestroy extends Factory {
    destroy: () => void
}

// Navigation Factory
export interface NavigationFactory extends FactoryWithDestroy {
    show: () => void
    hide: () => void
    toggle: () => void
}

// ScrollReveal Factory
export interface ScrollRevealFactory extends FactoryWithDestroy {
    reveal: (elements: Element[]) => void
}

// SmoothScroll Factory
export interface SmoothScrollFactory extends FactoryWithDestroy {
    scrollTo: (target: string | Element) => void
}

// NotificationManager Factory

// Parallax Factory
export interface ParallaxFactory extends FactoryWithDestroy {
    update: () => void
}

// Notification Manager Factory
export interface NotificationManagerFactory extends FactoryWithDestroy {
    show: (options: NotificationOptions) => void
    success: (message: string) => void
    error: (message: string) => void
    info: (message: string) => void
}

// LazyLoader Factory
export interface LazyLoaderFactory extends FactoryWithDestroy {
    loadImage: (img: HTMLImageElement) => void
}

// FAQ Accordion Factory
export interface FAQAccordionFactory extends FactoryWithDestroy {
    openItem: (index: number) => void
    closeAll: () => void
}

// Number Counter Factory
// Performance Monitor Factory
export interface PerformanceMonitorFactory extends FactoryWithDestroy {
    log: () => void
}
