/**
 * Event Registration Success Page - Auto Scroll
 * Scrolls to the registration form section when the success page loads
 */

document.addEventListener('DOMContentLoaded', () => {
  const registrationSection = document.getElementById('registrationForm');
  
  if (registrationSection) {
    // Sanftes Scrollen zum Registration-Bereich
    registrationSection.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'start' 
    });
    
    // Fallback für ältere Browser
    setTimeout(() => {
      window.location.hash = 'registrationForm';
    }, 100);
  }
});

