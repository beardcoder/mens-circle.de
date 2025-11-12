import { CONFIG } from '../config/index'
import type { Factory } from '../types/index'

/**
 * View Transitions API (2025)
 * Provides smooth page transitions using the native View Transitions API
 * Integrated with Hotwired Turbo for SPA-like navigation
 */
export const createViewTransitions = (): Factory => {
    // Check for View Transitions API support
    const supportsViewTransitions = 'startViewTransition' in document

    /**
     * Start a view transition
     */
    const startTransition = (callback: () => void): void => {
        if (!supportsViewTransitions) {
            callback()
            return
        }

        // @ts-expect-error - View Transitions API is not yet in TypeScript types
        document.startViewTransition(() => {
            callback()
        })
    }

    /**
     * Handle Turbo navigation with View Transitions
     */
    const handleTurboBeforeRender = (event: CustomEvent): void => {
        if (!supportsViewTransitions) {
            return
        }

        // Prevent default render
        event.preventDefault()

        // @ts-expect-error - View Transitions API is not yet in TypeScript types
        document.startViewTransition(() => {
            // Resume the Turbo render after transition setup
            event.detail.resume()
        })
    }

    /**
     * Handle Turbo form submissions with View Transitions
     */
    const handleTurboSubmitEnd = (event: CustomEvent): void => {
        if (!supportsViewTransitions || !event.detail.success) {
            return
        }

        // Add a subtle transition for form submissions
        document.body.classList.add('view-transition-form')

        setTimeout(() => {
            document.body.classList.remove('view-transition-form')
        }, 500)
    }

    /**
     * Initialize View Transitions
     */
    const init = (): void => {
        if (!supportsViewTransitions) {
            if (CONFIG.development) {
                console.info('⚠️ View Transitions API not supported in this browser. Using fallback navigation.')
            }
            return
        }

        // Integrate with Turbo
        document.addEventListener('turbo:before-render', handleTurboBeforeRender)

        document.addEventListener('turbo:submit-end', handleTurboSubmitEnd)

        if (CONFIG.development) {
            console.info('✨ View Transitions API enabled with Turbo integration')
        }
    }

    /**
     * Cleanup
     */
    const destroy = (): void => {
        document.removeEventListener('turbo:before-render', handleTurboBeforeRender)

        document.removeEventListener('turbo:submit-end', handleTurboSubmitEnd)

        if (CONFIG.development) {
            console.info('🧹 View Transitions destroyed')
        }
    }

    // Initialize
    init()

    return {
        startTransition,
        destroy,
        [Symbol.dispose]: destroy,
    }
}
