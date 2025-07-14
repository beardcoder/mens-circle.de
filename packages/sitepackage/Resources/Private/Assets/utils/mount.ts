/**
 * Mounts a callback exactly once per identifier, after DOM is ready.
 * Internally it marks the <html> element with a data attribute
 * (`data-mounted-{id}`), so repeated calls with the same id are no-ops.
 *
 * @param id        A unique string to distinguish this mount instance
 * @param callback  A function where you initialize your modules
 */
export function mount(id: string, callback: () => void): void {
    if (!id) {
        throw new Error('mount: "id" must be a non-empty string')
    }

    const html = document.documentElement
    const attrName = `data-mounted-${id}`

    const runOnce = (): void => {
        // If we’ve already mounted under this id, bail out
        if (html.hasAttribute(attrName)) {
            return
        }

        try {
            // Mark as mounted before running, so even if callback re-enters mount, it won’t loop
            html.setAttribute(attrName, 'true')
            callback()
        } catch (err) {
            // If your init throws, clean up the flag so you can retry
            html.removeAttribute(attrName)
            throw err
        }
    }

    // Run on DOMContentLoaded
    if (document.readyState === 'loading') {
        // Use `{ once: true }` so the listener auto-removes
        document.addEventListener('DOMContentLoaded', runOnce, {
            once: true,
        })
    } else {
        runOnce()
    }

    // Also run on Turbo page changes
    window.addEventListener('turbo:load', runOnce)
}
