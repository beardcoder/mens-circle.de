interface FormResponse {
  success: boolean
  message: string
  errors?: Record<string, string>
}

const submitForm = async (
  form: HTMLFormElement,
  endpoint: string,
): Promise<FormResponse> => {
  const formData = new FormData(form)
  const data = Object.fromEntries(formData.entries())

  const response = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(data),
  })

  return response.json()
}

const showErrors = (
  form: HTMLFormElement,
  errors: Record<string, string>,
): void => {
  clearErrors(form)

  for (const [field, message] of Object.entries(errors)) {
    const input = form.querySelector<HTMLInputElement>(`[name="${field}"]`)
    if (!input) continue

    input.setAttribute('aria-invalid', 'true')
    input.classList.add('is-invalid')

    const errorEl = document.createElement('span')
    errorEl.className = 'FormError'
    errorEl.setAttribute('role', 'alert')
    errorEl.textContent = message

    input.after(errorEl)
  }
}

const clearErrors = (form: HTMLFormElement): void => {
  form.querySelectorAll('.is-invalid').forEach((el) => {
    el.classList.remove('is-invalid')
    el.removeAttribute('aria-invalid')
  })

  form.querySelectorAll('.FormError').forEach((el) => el.remove())
}

const showMessage = (form: HTMLFormElement, message: string): void => {
  const messageEl = document.createElement('div')
  messageEl.className = 'FormMessage'
  messageEl.setAttribute('role', 'status')
  messageEl.textContent = message

  form.before(messageEl)
  form.remove()
}

export const initForms = (): void => {
  const forms = document.querySelectorAll<HTMLFormElement>('[data-ajax-form]')

  forms.forEach((form) => {
    const endpoint = form.dataset.ajaxForm

    if (!endpoint) return

    form.addEventListener('submit', async (event) => {
      event.preventDefault()

      const submitButton = form.querySelector<HTMLButtonElement>(
        'button[type="submit"]',
      )

      if (submitButton) {
        submitButton.disabled = true
        submitButton.setAttribute('aria-busy', 'true')
      }

      try {
        const result = await submitForm(form, endpoint)

        if (result.success) {
          showMessage(form, result.message)
        } else if (result.errors) {
          showErrors(form, result.errors)
        }
      } catch {
        showErrors(form, { _form: 'An unexpected error occurred.' })
      } finally {
        if (submitButton) {
          submitButton.disabled = false
          submitButton.removeAttribute('aria-busy')
        }
      }
    })
  })
}
