/**
 * Newsletter Form Module
 * Handles newsletter subscription form submission
 */

import { validateEmail } from '../../utils/validation'
import { showMessage } from '../../utils/message'
import { getCsrfToken, getApiEndpoint } from '../../utils/config'
import type { NewsletterData, FormSubmitResponse } from '../../types'

export function initNewsletterForm(): void {
  const newsletterForm = document.getElementById(
    'newsletterForm',
  ) as HTMLFormElement | null

  if (!newsletterForm) return

  newsletterForm.addEventListener('submit', function (e) {
    e.preventDefault()
    handleNewsletterSubmit(this)
  })
}

function handleNewsletterSubmit(form: HTMLFormElement): void {
  const messageContainer = document.getElementById('newsletterMessage')
  const emailInput = form.querySelector(
    'input[type="email"]',
  ) as HTMLInputElement
  const email = emailInput.value.trim()
  const submitButton = form.querySelector(
    'button[type="submit"]',
  ) as HTMLButtonElement

  if (!validateEmail(email)) {
    showMessage(
      messageContainer,
      'Bitte gib eine gültige E-Mail-Adresse ein.',
      'error',
    )
    return
  }

  // Disable button during submission
  submitButton.disabled = true
  submitButton.textContent = 'Wird gesendet...'

  const data: NewsletterData = { email }

  // Get API endpoint and CSRF token
  const apiUrl = getApiEndpoint('newsletter')
  const csrfToken = getCsrfToken()

  if (!apiUrl) {
    showMessage(
      messageContainer,
      'Konfigurationsfehler. Bitte kontaktiere den Administrator.',
      'error',
    )
    submitButton.disabled = false
    submitButton.textContent = 'Anmelden'
    return
  }

  // Send to backend
  fetch(apiUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      Accept: 'application/json',
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((data: FormSubmitResponse) => {
      if (data.success) {
        showMessage(messageContainer, data.message, 'success')
        form.reset()
      } else {
        showMessage(
          messageContainer,
          data.message || 'Ein Fehler ist aufgetreten.',
          'error',
        )
      }
    })
    .catch(() => {
      showMessage(
        messageContainer,
        'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.',
        'error',
      )
    })
    .finally(() => {
      submitButton.disabled = false
      submitButton.textContent = 'Anmelden'
    })
}
