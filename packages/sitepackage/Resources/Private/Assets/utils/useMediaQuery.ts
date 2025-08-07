import { type Signal, useSignal } from './createComponent'

export interface MediaQuerySignal extends Signal<boolean> {
    readonly query: string
}

export function useMediaQuery(query: string): MediaQuerySignal {
    const getMatches = () => (typeof window !== 'undefined' ? window.matchMedia(query).matches : false)

    const signal = useSignal<boolean>(getMatches())

    let mql: MediaQueryList | null = null
    let listener: ((this: MediaQueryList, ev: MediaQueryListEvent) => void) | null = null

    if (typeof window !== 'undefined') {
        mql = window.matchMedia(query)
        listener = e => {
            signal.value = e.matches
        }
        mql.addEventListener('change', listener)
    }

    // Optional: Cleanup if your framework supports it
    // useEffect(() => () => mql?.removeEventListener('change', listener), [])

    return Object.assign(signal, { query })
}
