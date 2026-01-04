/**
 * Content Elements Scripts
 * FAQ Accordion and Scroll Animations
 */

export const initFaqAccordion = (): void => {
  const faqItems = document.querySelectorAll('[data-faq-item]')

  faqItems.forEach((item) => {
    const trigger = item.querySelector('[data-faq-trigger]')
    const content = item.querySelector('[data-faq-content]')

    if (!trigger || !content) return

    trigger.addEventListener('click', () => {
      const isExpanded = trigger.getAttribute('aria-expanded') === 'true'

      // Close all other items
      faqItems.forEach((otherItem) => {
        const otherTrigger = otherItem.querySelector('[data-faq-trigger]')
        const otherContent = otherItem.querySelector('[data-faq-content]')
        if (otherTrigger && otherContent && otherItem !== item) {
          otherTrigger.setAttribute('aria-expanded', 'false')
          otherContent.setAttribute('aria-hidden', 'true')
        }
      })

      // Toggle current item
      trigger.setAttribute('aria-expanded', String(!isExpanded))
      content.setAttribute('aria-hidden', String(isExpanded))
    })
  })
}

export const initScrollAnimations = (): void => {
  const animatedElements = document.querySelectorAll('.fade-in, .stagger-children')

  if (!animatedElements.length) return

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible')
          observer.unobserve(entry.target)
        }
      })
    },
    {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px',
    }
  )

  animatedElements.forEach((el) => observer.observe(el))
}

export const initNewsletterForms = (): void => {
  const forms = document.querySelectorAll('[data-newsletter-form]')

  forms.forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault()

      const formElement = e.target as HTMLFormElement
      const messageEl = formElement.querySelector('[data-newsletter-message]')
      const emailInput = formElement.querySelector('input[type="email"]') as HTMLInputElement
      const submitBtn = formElement.querySelector('button[type="submit"]') as HTMLButtonElement

      if (!emailInput || !submitBtn) return

      submitBtn.disabled = true
      submitBtn.setAttribute('aria-busy', 'true')

      try {
        const response = await fetch('/newsletter/subscribe', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ email: emailInput.value }),
        })

        const data = await response.json()

        if (messageEl) {
          messageEl.textContent = data.message || (response.ok ? 'Successfully subscribed!' : 'An error occurred')
          messageEl.className = `newsletter__message newsletter__message--${response.ok ? 'success' : 'error'}`
        }

        if (response.ok) {
          emailInput.value = ''
        }
      } catch {
        if (messageEl) {
          messageEl.textContent = 'An error occurred. Please try again.'
          messageEl.className = 'newsletter__message newsletter__message--error'
        }
      } finally {
        submitBtn.disabled = false
        submitBtn.removeAttribute('aria-busy')
      }
    })
  })
}

export const initContentElements = (): void => {
  initFaqAccordion()
  initScrollAnimations()
  initNewsletterForms()
}
