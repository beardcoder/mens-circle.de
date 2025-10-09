/**
 * Newsletter Success Page - Auto Scroll
 * Scrolls to the newsletter section when the success page loads
 */

document.addEventListener('DOMContentLoaded', () => {
  const newsletterSection = document.getElementById('newsletter');

  if (newsletterSection) {
    // Sanftes Scrollen zum Newsletter-Bereich
    newsletterSection.scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });

    // Fallback für ältere Browser
    setTimeout(() => {
      window.location.hash = 'newsletter';
    }, 100);
  }
});

