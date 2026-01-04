import './Styles/main.css'
import { initForms } from './Scripts/forms'
import { initNavigation } from './Scripts/navigation'
import { initContentElements } from './Scripts/content-elements'

const init = (): void => {
  initNavigation()
  initForms()
  initContentElements()
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
