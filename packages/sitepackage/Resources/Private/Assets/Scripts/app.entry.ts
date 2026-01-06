/**
 * Männerkreis Straubing - TypeScript Application Entry Point
 * Handles navigation, FAQ accordion, forms, animations, and calendar integration
 */

import { initNavigation } from './modules/navigation'
import { initScrollHeader } from './modules/scrollHeader'
import { initFAQ } from './modules/faq'
import { initForms } from './modules/forms'
import { initScrollAnimations } from './modules/scrollAnimations'
import { initCalendarIntegration } from './modules/calendar'
import { initSmoothScroll } from './modules/smoothScroll'

document.addEventListener('DOMContentLoaded', () => {
  // Initialize all components
  initNavigation()
  initScrollHeader()
  initFAQ()
  initForms()
  initScrollAnimations()
  initCalendarIntegration()
  initSmoothScroll()
})
