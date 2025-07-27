import { createComponentAndMount } from './utils/createComponent.ts'

createComponentAndMount<HTMLDialogElement>('newsletter-dialog', '[data-component="newsletter-dialog"]', component => {
    const { element } = component

    const lastPopupTime = Number(localStorage.getItem('lastPopupTime'))
    const now = new Date().getTime()
    const twoHours = 2 * 60 * 60 * 1000

    if (!lastPopupTime || now - lastPopupTime > twoHours) {
        setTimeout(() => {
            if (element) {
                element?.showModal()
                setLastPopupTime()
            }
        }, 10_000)
    }

    component.querySelector('[data-component="newsletter-dialog__close"]')?.addEventListener('click', closeDialog)

    function closeDialog() {
        element?.close()
    }

    function setLastPopupTime() {
        const now = new Date().getTime()
        localStorage.setItem('lastPopupTime', String(now))
    }
})
