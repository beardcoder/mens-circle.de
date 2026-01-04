/**
 * Mobile Navigation Module
 * Handles mobile navigation toggle and interactions
 */

export function initNavigation(): void {
  const navToggle = document.getElementById('navToggle')
  const nav = document.getElementById('nav')

  if (!navToggle || !nav) return

  // Store scroll position
  let scrollPosition = 0

  const openNav = (): void => {
    scrollPosition = window.pageYOffset
    nav.classList.add('open')
    navToggle.classList.add('active')
    document.body.classList.add('nav-open')
    document.body.style.top = `-${scrollPosition}px`
    navToggle.setAttribute('aria-expanded', 'true')
    navToggle.setAttribute('aria-label', 'Menü schließen')
  }

  const closeNav = (): void => {
    nav.classList.remove('open')
    navToggle.classList.remove('active')
    document.body.classList.remove('nav-open')
    document.body.style.top = ''
    window.scrollTo({ top: scrollPosition, left: 0, behavior: 'instant' })
    navToggle.setAttribute('aria-expanded', 'false')
    navToggle.setAttribute('aria-label', 'Menü öffnen')
  }

  navToggle.addEventListener('click', () => {
    const isOpen = nav.classList.contains('open')

    if (isOpen) {
      closeNav()
    } else {
      openNav()
    }
  })

  // Close nav when clicking on a link
  const navLinks = nav.querySelectorAll('.nav__link, .nav__cta')

  navLinks.forEach((link) => {
    link.addEventListener('click', () => {
      closeNav()
    })
  })

  // Close nav when clicking outside
  document.addEventListener('click', (e: MouseEvent) => {
    if (
      !nav.contains(e.target as Node) &&
      !navToggle.contains(e.target as Node) &&
      nav.classList.contains('open')
    ) {
      closeNav()
    }
  })

  // Close nav on Escape key
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && nav.classList.contains('open')) {
      closeNav()
    }
  })
}
