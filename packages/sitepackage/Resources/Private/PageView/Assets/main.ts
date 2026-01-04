import './Styles/main.css'
import { initForms } from './Scripts/forms'
import { initNavigation } from './Scripts/navigation'

const init = (): void => {
  initNavigation()
  initForms()
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}
