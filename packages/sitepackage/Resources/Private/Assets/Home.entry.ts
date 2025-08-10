import { createComponentAndMount, useLocalStorage } from '@beardcoder/simple-components'

void createComponentAndMount<HTMLDialogElement>('[data-component="newsletter-dialog"]', component => {
    const { element } = component
    const now = Date.now()
    const lastPopupTime = useLocalStorage('lastPopupTime', now)
    const twoHours = 2 * 60 * 60 * 1000
    const closeButton = component.querySelector<HTMLButtonElement>('[data-component="newsletter-dialog__close"]')

    let timeoutId: ReturnType<typeof setTimeout> | undefined

    const shouldShowDialog = !lastPopupTime.value || now - lastPopupTime.value > twoHours

    const showDialog = (e: MouseEvent) => {
        e.preventDefault()

        if (element) {
            element.showModal()
            lastPopupTime.value = Date.now()
        }
    }

    if (shouldShowDialog) {
        timeoutId = setTimeout(showDialog, 10_000)
    }

    const onCloseDialog = () => {
        element?.close()
    }

    closeButton?.addEventListener('click', onCloseDialog)

    return () => {
        closeButton?.removeEventListener('click', onCloseDialog)
        if (timeoutId) clearTimeout(timeoutId)
    }
})
