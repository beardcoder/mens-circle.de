import type { AppConfig } from '../types/index'

/**
 * Application configuration
 */
export const CONFIG: AppConfig = {
    navigation: {
        scrollThreshold: 100,
        hideOnScroll: true,
    },
    animations: {
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    },
    development: ['localhost', '127.0.0.1', 'mens-circle.ddev.site'].includes(window.location.hostname),
}
