/**
 * Forms Module
 * Entry point for all form initializations
 */

import { initNewsletterForm } from './newsletter';
import { initRegistrationForm } from './registration';
import { initTestimonialForm } from './testimonial';

export function initForms(): void {
  initNewsletterForm();
  initRegistrationForm();
  initTestimonialForm();
}
