/**
 * Testimonial Form Module
 * Handles testimonial submission form
 */

import { validateEmail } from '../../utils/validation'
import { showMessage } from '../../utils/message'
import type { TestimonialData, FormSubmitResponse } from '../../types'

export function initTestimonialForm(): void {
  const testimonialForm = document.getElementById(
    'testimonialForm',
  ) as HTMLFormElement | null

  if (!testimonialForm) return

  // Character counter
  const quoteTextarea = testimonialForm.querySelector(
    '#quote',
  ) as HTMLTextAreaElement | null
  const charCount = document.getElementById('charCount')

  if (quoteTextarea && charCount) {
    quoteTextarea.addEventListener('input', function () {
      charCount.textContent = this.value.length.toString()
    })
  }

  testimonialForm.addEventListener('submit', function (e) {
    e.preventDefault()
    handleTestimonialSubmit(this)
  })
}

function handleTestimonialSubmit(form: HTMLFormElement): void {
  const messageContainer = document.getElementById('formMessage')
  const formData = new FormData(form)
  const submitButton = form.querySelector(
    'button[type="submit"]',
  ) as HTMLButtonElement
  const submitText = submitButton.querySelector(
    '.btn__text',
  ) as HTMLElement | null
  const submitLoader = submitButton.querySelector(
    '.btn__loader',
  ) as HTMLElement | null

  const quote = formData.get('quote')?.toString().trim()
  const authorName = formData.get('author_name')?.toString().trim() || null
  const role = formData.get('role')?.toString().trim() || null
  const email = formData.get('email')?.toString().trim()
  const privacy = (
    form.querySelector('input[name="privacy"]') as HTMLInputElement
  )?.checked

  // Validation
  if (!quote || quote.length < 10) {
    showMessage(
      messageContainer,
      'Bitte teile deine Erfahrung mit uns (mindestens 10 Zeichen).',
      'error',
    )
    return
  }

  if (!email || !validateEmail(email)) {
    showMessage(
      messageContainer,
      'Bitte gib eine gültige E-Mail-Adresse ein.',
      'error',
    )
    return
  }

  if (!privacy) {
    showMessage(
      messageContainer,
      'Bitte bestätige die Datenschutzerklärung.',
      'error',
    )
    return
  }

  // Disable button during submission
  submitButton.disabled = true

  if (submitText) {
    submitText.style.display = 'none'
  }

  if (submitLoader) {
    submitLoader.style.display = 'inline-block'
  }

  const submitUrl = form.getAttribute('data-submit-url') || ''

  const data: TestimonialData = {
    quote,
    author_name: authorName,
    role,
    email,
    privacy: privacy ? 1 : 0,
  }

  // Send to backend
  fetch(submitUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN':
        document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute('content') || '',
      Accept: 'application/json',
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((data: FormSubmitResponse) => {
      if (data.success) {
        showMessage(messageContainer, data.message, 'success')
        form.reset()

        // Reset character counter
        const charCount = document.getElementById('charCount')

        if (charCount) {
          charCount.textContent = '0'
        }
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

      if (submitText) {
        submitText.style.display = 'inline'
      }

      if (submitLoader) {
        submitLoader.style.display = 'none'
      }
    })
}
