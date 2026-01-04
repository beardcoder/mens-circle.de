/**
 * FAQ Accordion Module
 * Handles the FAQ accordion functionality
 */

export function initFAQ(): void {
  const faqItems = document.querySelectorAll('.faq-item')

  faqItems.forEach((item) => {
    const question = item.querySelector('.faq-item__question')

    if (!question) return

    question.addEventListener('click', () => {
      const isActive = item.classList.contains('active')

      // Close all other items
      faqItems.forEach((otherItem) => {
        if (otherItem !== item) {
          otherItem.classList.remove('active')
          otherItem
            .querySelector('.faq-item__question')
            ?.setAttribute('aria-expanded', 'false')
        }
      })

      // Toggle current item
      item.classList.toggle('active')
      question.setAttribute('aria-expanded', String(!isActive))
    })
  })
}
