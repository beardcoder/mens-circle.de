/**
 * Mounts a callback exactly once per identifier, after DOM is ready.
 * Internally it marks the <documentBody> element with a data attribute
 * (`data-mounted-{id}`), so repeated calls with the same id are no-ops.
 *
 * @param id        A unique string to distinguish this mount instance
 * @param callback  A function where you initialize your modules
 */
export function mount(id: string, callback: () => void): void {
    if (!id) {
        throw new Error('mount: "id" must be a non-empty string')
    }

    const attrName = `data-mounted-${id}`

    const runOnce = (): void => {
        const documentBody = document.body

        if (documentBody.hasAttribute(attrName)) {
            return
        }

        try {
            // Mark as mounted before running, so even if callback re-enters mount, it won’t loop
            documentBody.setAttribute(attrName, 'true')
            callback()
        } catch (err) {
            // If your init throws, clean up the flag so you can retry
            documentBody.removeAttribute(attrName)
            throw err
        }
    }

    const isTurbo = typeof Turbo === 'object' && typeof Turbo.navigator === 'object'
    const loadEvent = isTurbo ? 'turbo:load' : 'DOMContentLoaded'

    if (document.readyState === 'loading' || isTurbo) {
        document.addEventListener(loadEvent, runOnce, { once: !isTurbo })
    } else {
        runOnce()
    }
}
