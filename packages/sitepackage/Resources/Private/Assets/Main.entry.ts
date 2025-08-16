import '@fontsource-variable/playfair-display/wght-italic.css'
import '@fontsource-variable/jost'
import { animate, inView } from 'motion'
import '@hotwired/turbo'
import * as Sentry from '@sentry/browser'
import { mount } from './utils/mount.ts'
import './Components/Card.ts'

const init = () => {
    Sentry.init({
        dsn: 'https://3c75af6221aa112964afd403d8b4f7dc@o4508569353977856.ingest.de.sentry.io/4509593780224080',
        sendDefaultPii: true,
        tracesSampleRate: 1.0,
    })

    inView('[data-animate="fadeUp"]', (element) => {
        const delay = (element as HTMLElement).dataset?.delay ?? 0
        const duration = (element as HTMLElement).dataset?.duration ?? 0.5
        animate(
            element,
            {
                opacity: 1,
                y: [40, 0],
            },
            {
                delay: delay as number,
                duration: duration as number,
            },
        )
    })

    inView('[data-animate="fadeDown"]', (element) => {
        const delay = (element as HTMLElement).dataset?.delay ?? 0
        const duration = (element as HTMLElement).dataset?.duration ?? 0.5
        animate(
            element,
            {
                opacity: 1,
                y: [-40, 0],
            },
            {
                delay: delay as number,
                duration: duration as number,
            },
        )
    })
}

mount('init', init)
