import { CONFIG } from './config'
import { createNavigation } from './components/navigation.ts'
import { createSmoothScroll } from './components/smoothScroll.ts'
import type { Factory } from './types'

const factories: Factory[] = []

const registerFactory = (factory?: Factory | null): void => {
    if (factory) {
        factories.push(factory)
    }
}

const loadUtilities = (() => {
    let promise: Promise<typeof import('./components/utilities.ts')> | null = null

    return () => {
        if (!promise) {
            promise = import('./components/utilities.ts')
        }

        return promise
    }
})()

const bootstrap = async (): Promise<void> => {
    registerFactory(createNavigation())
    registerFactory(createSmoothScroll())

    if (document.querySelector('img[data-src]')) {
        const { createLazyImageLoader } = await loadUtilities()
        registerFactory(createLazyImageLoader())
    }

    if (document.querySelector('.reveal-card, .reveal-text')) {
        const { createScrollReveal } = await import('./components/scrollReveal.ts')
        registerFactory(createScrollReveal())
    }

    if (document.querySelector('.faq__item')) {
        const { createFAQAccordion } = await loadUtilities()
        registerFactory(createFAQAccordion())
    }

    if (CONFIG.development) {
        const { createPerformanceMonitor } = await loadUtilities()
        registerFactory(createPerformanceMonitor())
    }

    document.body.classList.add('is-loaded')
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        void bootstrap()
    })
} else {
    void bootstrap()
}

if (CONFIG.development) {
    ;(window as any).disposeApp = () => {
        factories.forEach((factory) => factory[Symbol.dispose]?.())
        factories.length = 0
    }
}
