import { createComponentAndMount } from '@beardcoder/simple-components'

void createComponentAndMount<HTMLDivElement>("[data-component='card']", (component) => {
    const { element } = component

    const mainLink = component.querySelector<HTMLAnchorElement>('a')
    const clickable = Array.from(component.querySelectorAll('a'))

    // Don’t let inner controls trigger the card’s click
    clickable.forEach((el) => el.addEventListener('click', (e) => e.stopPropagation()))

    // Make the card a focusable “button”
    element.tabIndex ||= 0
    element.setAttribute('role', 'button')
    element.style.cursor = 'pointer'

    const handler = (e: MouseEvent | KeyboardEvent) => {
        // keyboard “click”
        if (e instanceof KeyboardEvent) {
            if (!['Enter', ' '].includes(e.key) || document.activeElement !== element) return
            e.preventDefault()
        }
        // no text selection, and didn’t hit a real control
        if (window.getSelection()?.toString()) return
        if (clickable.some((el) => el.contains(e.target as Node))) return

        mainLink?.click()
    }

    element.addEventListener('click', handler)
    element.addEventListener('keydown', handler)

    // Cleanup function to remove event listeners
    return () => {
        element.removeEventListener('click', handler)
        element.removeEventListener('keydown', handler)
        clickable.forEach((el) => el.removeEventListener('click', (e) => e.stopPropagation()))
    }
})
