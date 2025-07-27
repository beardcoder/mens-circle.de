import { createComponent, createComponentAndMount } from '../utils/createComponent.ts'
import { mount } from '../utils/mount.ts'

void createComponentAndMount<HTMLDivElement>('card', "[data-component='card']", component => {
    const { element } = component

    const mainLink = component.querySelector<HTMLAnchorElement>('a')
    const clickables = Array.from(component.querySelectorAll('a'))

    // Don’t let inner controls trigger the card’s click
    clickables.forEach(el => el.addEventListener('click', e => e.stopPropagation()))

    // Make the card a focusable “button”
    element.tabIndex ||= 0
    element.setAttribute('role', 'button')
    element.style.cursor = 'pointer'

    const handler = (e: MouseEvent | KeyboardEvent) => {
        // keyboard “click”
        if (e instanceof KeyboardEvent) {
            if (!['Enter', ' '].includes(e.key) || document.activeElement !== card) return
            e.preventDefault()
        }
        // no text selection, and didn’t hit a real control
        if (window.getSelection()?.toString()) return
        if (clickables.some(el => el.contains(e.target as Node))) return

        mainLink?.click()
    }

    element.addEventListener('click', handler)
    element.addEventListener('keydown', handler)
})
