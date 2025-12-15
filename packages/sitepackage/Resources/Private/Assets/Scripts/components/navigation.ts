import { CONFIG } from '../config/index'
import { debounce, throttle } from '../utils/functions'
import type { NavigationFactory } from '../types/index'

/**
 * Navigation Factory
 * Creates a responsive navigation with scroll behavior
 */
export const createNavigation = (): NavigationFactory => {
    // DOM Elements
    const nav = document.getElementById('nav')
    const navToggle = document.getElementById('navToggle')
    const navMenu = document.getElementById('navMenu')
    const navLinks = navMenu?.querySelectorAll('[data-nav-link]') ?? null

    // State
    let lastScrollY = window.scrollY
    const scrollThreshold = CONFIG.navigation.scrollThreshold

    // Mobile Menu Management
    const toggleMenu = (): void => {
        if (!navMenu || !navToggle) return

        const isActive = navMenu.classList.toggle('is-active')
        navToggle.classList.toggle('is-active')
        navToggle.setAttribute('aria-expanded', String(isActive))
        navToggle.setAttribute('aria-label', isActive ? 'Navigation schließen' : 'Navigation öffnen')

        // Toggle body class for overlay
        document.body.classList.toggle('nav-open', isActive)
    }

    const closeMenu = (): void => {
        if (!navMenu || !navToggle) return
        if (!navMenu.classList.contains('is-active')) return

        navMenu.classList.remove('is-active')
        navToggle.classList.remove('is-active')
        navToggle.setAttribute('aria-expanded', 'false')
        navToggle.setAttribute('aria-label', 'Navigation öffnen')
        document.body.classList.remove('nav-open')
    }

    // Scroll Behavior
    const handleScroll = (): void => {
        if (!nav) return

        const currentScrollY = window.scrollY

        // Add/remove scrolled class for styling
        if (currentScrollY > 50) {
            nav.classList.add('nav--scrolled')
        } else {
            nav.classList.remove('nav--scrolled')
        }

        // Hide/show nav on scroll
        if (CONFIG.navigation.hideOnScroll && currentScrollY > scrollThreshold) {
            if (currentScrollY > lastScrollY && currentScrollY > 200) {
                // Scrolling down - hide nav
                nav.classList.add('nav--hidden')
            } else if (currentScrollY < lastScrollY) {
                // Scrolling up - show nav
                nav.classList.remove('nav--hidden')
            }
        }

        lastScrollY = currentScrollY
    }

    // Active Link Highlighting
    const updateActiveLink = (): void => {
        if (!navLinks) return

        const sections = document.querySelectorAll('section[id]')
        const scrollPosition = window.scrollY + 100

        sections.forEach((section) => {
            const sectionTop = (section as HTMLElement).offsetTop
            const sectionHeight = (section as HTMLElement).offsetHeight
            const sectionId = section.getAttribute('id')

            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navLinks.forEach((link) => {
                    link.classList.remove('is-active')
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('is-active')
                    }
                })
            }
        })
    }

    // Resize Handler
    const handleResize = (): void => {
        if (window.innerWidth > 768) {
            closeMenu()
        }
    }

    // Click Outside Handler
    const handleClickOutside = (e: MouseEvent): void => {
        if (navMenu?.classList.contains('is-active') && !nav?.contains(e.target as Node)) {
            closeMenu()
        }
    }

    // Escape Key Handler
    const handleEscape = (e: KeyboardEvent): void => {
        if (e.key === 'Escape' && navMenu?.classList.contains('is-active')) {
            closeMenu()
        }
    }

    // Event Listeners
    const throttledScroll = throttle(() => {
        handleScroll()
        updateActiveLink()
    }, 100)
    const debouncedResize = debounce(handleResize, 150)

    // Initialize
    const init = (): void => {
        if (!nav) return

        // Mobile menu toggle
        navToggle?.addEventListener('click', toggleMenu)

        // Close menu on link click
        navLinks?.forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeMenu()
                }
            })
        })

        // Scroll behavior and active links
        window.addEventListener('scroll', throttledScroll)

        // Resize handler
        window.addEventListener('resize', debouncedResize)

        // Click outside to close
        document.addEventListener('click', handleClickOutside)

        // Escape to close
        document.addEventListener('keydown', handleEscape)

        // Initial active link update
        updateActiveLink()

        if (CONFIG.development) {
            console.info('✨ Navigation initialized')
        }
    }

    // Public API
    const show = (): void => {
        nav?.classList.remove('nav--hidden')
    }

    const hide = (): void => {
        nav?.classList.add('nav--hidden')
    }

    const toggle = (): void => {
        toggleMenu()
    }

    const destroy = (): void => {
        // Remove all event listeners
        navToggle?.removeEventListener('click', toggleMenu)
        window.removeEventListener('scroll', throttledScroll)
        window.removeEventListener('resize', debouncedResize)
        document.removeEventListener('click', handleClickOutside)
        document.removeEventListener('keydown', handleEscape)

        navLinks?.forEach((link) => {
            link.removeEventListener('click', closeMenu)
        })

        // Clean up state
        closeMenu()

        if (CONFIG.development) {
            console.info('🧹 Navigation destroyed')
        }
    }

    // Initialize and return public API
    init()

    return {
        show,
        hide,
        toggle,
        destroy,
        // ES2023: Symbol.dispose for automatic cleanup
        [Symbol.dispose]: destroy,
    }
}
