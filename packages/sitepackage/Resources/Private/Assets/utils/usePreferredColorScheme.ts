// File: utils/usePreferredColorScheme.ts
import { useMediaQuery } from './useMediaQuery'
import { useSignal, type Signal } from './createComponent'

const queries = {
  dark: '(prefers-color-scheme: dark)',
  light: '(prefers-color-scheme: light)',
  noPreference: '(prefers-color-scheme: no-preference)',
}

export type ColorScheme = 'dark' | 'light' | 'no-preference'

export function usePreferredColorScheme(): Signal<ColorScheme> {
  const isDark = useMediaQuery(queries.dark)
  const isLight = useMediaQuery(queries.light)
  const isNoPref = useMediaQuery(queries.noPreference)

  const scheme = useSignal<ColorScheme>('light')

  const update = () => {
    if (isDark.value) scheme.value = 'dark'
    else if (isLight.value) scheme.value = 'light'
    else if (isNoPref.value) scheme.value = 'no-preference'
    else scheme.value = 'light'
  }

  // Subscribe to changes
  isDark.subscribe(update)
  isLight.subscribe(update)
  isNoPref.subscribe(update)

  // Initialize
  update()

  return scheme
}
