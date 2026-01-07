/**
 * Scroll Animations Module
 * Handles scroll-triggered animations using IntersectionObserver
 */

export function initScrollAnimations(): void {
  const fadeElements = document.querySelectorAll('.fade-in');
  const staggerElements = document.querySelectorAll('.stagger-children');

  const allAnimatedElements = [...fadeElements, ...staggerElements];

  if (!allAnimatedElements.length) return;

  // Check if IntersectionObserver is supported
  if (!('IntersectionObserver' in window)) {
    // Fallback: show all elements immediately
    allAnimatedElements.forEach((el) => el.classList.add('visible'));

    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    },
    {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px',
    },
  );

  allAnimatedElements.forEach((el) => observer.observe(el));
}
