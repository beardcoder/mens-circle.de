/**
 * Smooth Scroll Module
 * Handles smooth scrolling for anchor links
 */

export function initSmoothScroll(): void {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e: Event) {
      const targetId = (this as HTMLAnchorElement).getAttribute('href')

      // Skip if empty anchor or not a valid selector (e.g., blob URLs)
      if (
        targetId === '#' ||
        !targetId?.startsWith('#') ||
        targetId.includes(':')
      ) {
        return
      }

      try {
        const target = document.querySelector(targetId)

        if (target) {
          e.preventDefault()
          const headerHeight =
            document.getElementById('header')?.offsetHeight || 0
          const targetPosition =
            target.getBoundingClientRect().top +
            window.pageYOffset -
            headerHeight -
            20

          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth',
          })
        }
      } catch {
        // Invalid selector - ignore silently
      }
    })
  })
}
