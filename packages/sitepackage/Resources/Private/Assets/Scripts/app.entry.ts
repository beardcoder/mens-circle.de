/**
 * Männerkreis Straubing - TypeScript Application Entry Point
 *
 * This is the main entry point for the TYPO3 v14 frontend application.
 * It initializes all interactive components using modern JavaScript patterns:
 * - Mobile navigation with accessibility features
 * - Sticky header on scroll
 * - FAQ accordion with ARIA support
 * - Form validation and submission (newsletter, registration, testimonial)
 * - Scroll-triggered animations via IntersectionObserver
 * - Calendar integration for events
 * - Smooth scrolling for anchor links
 *
 * All modules follow TYPO3 v14 best practices:
 * - ES modules with TypeScript
 * - Proper null checks and early returns
 * - Progressive enhancement
 * - Accessibility (ARIA attributes, keyboard navigation)
 * - Data attributes for configuration
 */

import { initNavigation } from './modules/navigation'
import { initScrollHeader } from './modules/scrollHeader'
import { initFAQ } from './modules/faq'
import { initForms } from './modules/forms'
import { initScrollAnimations } from './modules/scrollAnimations'
import { initCalendarIntegration } from './modules/calendar'
import { initSmoothScroll } from './modules/smoothScroll'

/**
 * Initialize all application modules
 */
function initApp(): void {
  try {
    // Initialize core navigation and UI components
    initNavigation()
    initScrollHeader()
    initFAQ()

    // Initialize form handlers
    initForms()

    // Initialize visual enhancements
    initScrollAnimations()
    initSmoothScroll()

    // Initialize calendar features
    initCalendarIntegration()
  } catch (error) {
    // Log errors in development, fail silently in production
    if (import.meta.env.DEV) {
      console.error('Error initializing application:', error)
    }
  }
}

// Wait for DOM to be fully loaded before initializing
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp)
} else {
  // DOM is already loaded, initialize immediately
  initApp()
}
