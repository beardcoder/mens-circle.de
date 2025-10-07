import type { DebouncedFunction, ThrottledFunction } from "../types/index";

/**
 * ES2023+ Helper: Creates a disposable object with Symbol.dispose
 * This allows automatic cleanup with the 'using' keyword
 */
export function makeDisposable<T extends Record<string, any>>(
  obj: T,
  disposeFunc: () => void,
): T & Disposable {
  return {
    ...obj,
    destroy: disposeFunc,
    [Symbol.dispose]: disposeFunc,
  };
}

/**
 * Debounce function - delays execution until after wait time has passed
 * @param func - Function to debounce
 * @param wait - Wait time in milliseconds
 * @returns Debounced function
 */
export function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number = 20,
): DebouncedFunction<T> {
  let timeout: ReturnType<typeof setTimeout> | null = null;

  return function executedFunction(this: any, ...args: Parameters<T>): void {
    const later = (): void => {
      timeout = null;
      func.apply(this, args);
    };

    if (timeout) {
      clearTimeout(timeout);
    }
    timeout = setTimeout(later, wait);
  };
}

/**
 * Throttle function - limits execution rate
 * @param func - Function to throttle
 * @param limit - Time limit in milliseconds
 * @returns Throttled function
 */
export function throttle<T extends (...args: any[]) => any>(
  func: T,
  limit: number = 100,
): ThrottledFunction<T> {
  let inThrottle = false;

  return function executedFunction(this: any, ...args: Parameters<T>): void {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => (inThrottle = false), limit);
    }
  };
}
