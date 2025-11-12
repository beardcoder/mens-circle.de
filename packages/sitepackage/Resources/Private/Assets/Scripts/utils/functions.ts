import type { DebouncedFunction, ThrottledFunction } from '../types'

/**
 * Debounce function - delays execution until after wait time has passed
 * @param func - Function to debounce
 * @param wait - Wait time in milliseconds
 * @returns Debounced function
 */
export function debounce<T extends () => any>(func: T, wait: number = 20): DebouncedFunction<T> {
    let timeout: ReturnType<typeof setTimeout> | null = null

    return function executedFunction(this: any, ...args: Parameters<T>): void {
        const later = (): void => {
            timeout = null
            func.apply(this, args)
        }

        if (timeout) {
            clearTimeout(timeout)
        }
        timeout = setTimeout(later, wait)
    }
}

/**
 * Throttle function - limits execution rate
 * @param func - Function to throttle
 * @param limit - Time limit in milliseconds
 * @returns Throttled function
 */
export function throttle<T extends () => any>(func: T, limit: number = 100): ThrottledFunction<T> {
    let inThrottle = false

    return function executedFunction(this: any, ...args: Parameters<T>): void {
        if (!inThrottle) {
            func.apply(this, args)
            inThrottle = true
            setTimeout(() => (inThrottle = false), limit)
        }
    }
}
