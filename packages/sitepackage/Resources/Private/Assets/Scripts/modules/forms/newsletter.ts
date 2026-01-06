/**
 * Newsletter Form Module
 * Handles newsletter subscription form submission
 */

import { validateEmail } from '../../utils/validation'
import { showMessage } from '../../utils/message'
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

  // Send to backend
  fetch(window.routes.newsletter, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': window.routes.csrfToken,
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
