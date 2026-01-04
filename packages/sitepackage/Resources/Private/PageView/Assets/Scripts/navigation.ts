const BREAKPOINT = 768

export const initNavigation = (): void => {
  const toggle = document.querySelector<HTMLButtonElement>(
    '.Navigation__toggle',
  )
  const nav = document.querySelector<HTMLElement>('.Navigation__list')

  if (!toggle || !nav) return

  const isDesktop = (): boolean => window.innerWidth >= BREAKPOINT

  const open = (): void => {
    toggle.setAttribute('aria-expanded', 'true')
    nav.classList.add('is-open')
  }

  const close = (): void => {
    toggle.setAttribute('aria-expanded', 'false')
    nav.classList.remove('is-open')
  }

  toggle.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') === 'true'
    isOpen ? close() : open()
  })

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      close()
    }
  })

  window.addEventListener('resize', () => {
    if (isDesktop()) {
      close()
    }
  })
}
