/**
 * Scroll Header Module
 * Adds a 'scrolled' class to the header when the user scrolls down
 */

export function initScrollHeader(): void {
  const header = document.getElementById('header')

  if (!header) return

  window.addEventListener(
    'scroll',
    () => {
      const currentScroll = window.pageYOffset

      if (currentScroll > 50) {
        header.classList.add('scrolled')
      } else {
        header.classList.remove('scrolled')
      }
    },
    { passive: true },
  )
}
