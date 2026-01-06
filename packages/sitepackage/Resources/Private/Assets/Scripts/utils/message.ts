import type { MessageType } from '../types'

/**
 * Default duration for auto-hiding messages (in milliseconds)
 */
const DEFAULT_HIDE_DURATION = 5000

/**
 * Displays a message to the user
 * @param container - The container element to display the message in
 * @param message - The message text to display
 * @param type - The message type (success or error)
 * @param autohide - Whether to auto-hide the message (default: true)
 * @param duration - Duration before auto-hide in milliseconds (default: 5000)
 */
export function showMessage(
  container: HTMLElement | null,
  message: string,
  type: MessageType,
  autohide = true,
  duration = DEFAULT_HIDE_DURATION,
): void {
  if (!container) return

  // Create message element
  const messageElement = document.createElement('div')

  messageElement.className = `form-message form-message--${type}`
  messageElement.textContent = message
  messageElement.setAttribute('role', type === 'error' ? 'alert' : 'status')
  messageElement.setAttribute('aria-live', 'polite')

  // Clear existing messages and add new one
  container.innerHTML = ''
  container.style.display = 'block'
  container.appendChild(messageElement)

  // Auto-hide after duration if enabled
  if (autohide) {
    setTimeout(() => {
      hideMessage(container)
    }, duration)
  }
}

/**
 * Hides a message container
 * @param container - The container element to hide
 */
export function hideMessage(container: HTMLElement | null): void {
  if (!container) return

  container.innerHTML = ''
  container.style.display = 'none'
}
