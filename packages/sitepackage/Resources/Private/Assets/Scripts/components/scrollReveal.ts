import type { ObserverOptions, ScrollRevealFactory } from '../types/index'

/**
 * ScrollReveal Factory
 * Creates scroll reveal animations using Intersection Observer
 */
export const createScrollReveal = (): ScrollRevealFactory => {
    // State
    const elements = {
        text: document.querySelectorAll('.reveal-text'),
        cards: document.querySelectorAll('.reveal-card'),
    }

    const observerOptions: ObserverOptions = {
        root: null,
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px',
    }

    let observer: IntersectionObserver | null = null

    // Private functions
    const handleIntersection = (entries: IntersectionObserverEntry[]): void => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible')
                // Unobserve after reveal for performance
                observer?.unobserve(entry.target)
            }
        })
    }

    const revealAll = (): void => {
        const allElements = [...Array.from(elements.text), ...Array.from(elements.cards)]

        allElements.forEach((el) => {
            el.classList.add('is-visible')
        })
    }

    // Initialize
    const init = (): void => {
        if (!('IntersectionObserver' in window)) {
            // Fallback: Show all elements immediately
            revealAll()
            return
        }

        observer = new IntersectionObserver(handleIntersection, observerOptions)

        // Observe all reveal elements
        const allElements = [...Array.from(elements.text), ...Array.from(elements.cards)]

        allElements.forEach((el) => {
            observer?.observe(el)
        })
    }

    // Public API
    const reveal = (newElements: Element[]): void => {
        if (!observer) return

        const elementsArray = Array.isArray(newElements) ? newElements : Array.from(newElements)

        elementsArray.forEach((el) => {
            observer?.observe(el)
        })
    }

    const destroy = (): void => {
        if (observer) {
            observer.disconnect()
            observer = null
        }
    }

    // Initialize and return public API with ES2023 Resource Management
    init()

    return {
        reveal,
        destroy,
        [Symbol.dispose]: destroy,
    }
}
