/**
 * Configuration utilities for retrieving data from DOM
 */

/**
 * Gets a configuration value from a data attribute or falls back to window object
 * @param attributeName - The data attribute name (without 'data-' prefix)
 * @param fallbackKey - Key to look up in window object if attribute not found
 * @returns The configuration value or empty string
 */
export function getConfigValue(attributeName: string, fallbackKey?: string): string {
  // Try to get from meta tag first
  const metaTag = document.querySelector(`meta[name="${attributeName}"]`) as HTMLMetaElement | null;

  if (metaTag?.content) {
    return metaTag.content;
  }

  // Fallback to window object for backwards compatibility
  if (fallbackKey && typeof window !== 'undefined') {
    const value = (window as Record<string, unknown>)[fallbackKey];

    if (typeof value === 'string') {
      return value;
    }
  }

  return '';
}

/**
 * Gets CSRF token from meta tag or data attribute
 * @returns The CSRF token
 */
export function getCsrfToken(): string {
  // Try meta tag first (standard TYPO3 v14 approach)
  const metaTag = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;

  if (metaTag?.content) {
    return metaTag.content;
  }

  // Fallback to window.routes for backwards compatibility
  if (typeof window !== 'undefined' && 'routes' in window) {
    const routes = window.routes as { csrfToken?: string };

    return routes.csrfToken || '';
  }

  return '';
}

/**
 * Gets API endpoint URL from data attribute or window object
 * @param endpoint - The endpoint name
 * @returns The full endpoint URL
 */
export function getApiEndpoint(endpoint: string): string {
  // Try data attribute on form or body
  const form = document.querySelector(`[data-api-${endpoint}]`);

  if (form) {
    const url = form.getAttribute(`data-api-${endpoint}`);

    if (url) return url;
  }

  // Fallback to window.routes
  if (typeof window !== 'undefined' && 'routes' in window) {
    const routes = window.routes as Record<string, string>;

    return routes[endpoint] || '';
  }

  return '';
}
