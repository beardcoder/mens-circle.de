import type { MessageType } from '../types'

/**
 * Displays a message to the user
 * @param container - The container element to display the message in
 * @param message - The message text to display
 * @param type - The message type (success or error)
 */
export function showMessage(
  container: HTMLElement | null,
  message: string,
  type: MessageType,
): void {
  if (!container) return

  container.style.display = 'block'
  container.innerHTML = `<div class="form-message form-message--${type}">${message}</div>`

  // Auto-hide after 5 seconds
  setTimeout(() => {
    container.innerHTML = ''
    container.style.display = 'none'
  }, 5000)
}
