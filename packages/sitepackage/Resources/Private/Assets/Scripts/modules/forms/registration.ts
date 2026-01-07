/**
 * Event Registration Form Module
 * Handles event registration form submission
 */

import { validateEmail } from '../../utils/validation';
import { showMessage } from '../../utils/message';
import { getCsrfToken, getApiEndpoint } from '../../utils/config';
import type { EventRegistrationData, FormSubmitResponse } from '../../types';

export function initRegistrationForm(): void {
  const registrationForm = document.getElementById('registrationForm') as HTMLFormElement | null;

  if (!registrationForm) return;

  registrationForm.addEventListener('submit', function (e) {
    e.preventDefault();
    handleRegistrationSubmit(this);
  });
}

function handleRegistrationSubmit(form: HTMLFormElement): void {
  const messageContainer = document.getElementById('registrationMessage');
  const formData = new FormData(form);
  const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement;

  const firstName = formData.get('first_name')?.toString().trim();
  const lastName = formData.get('last_name')?.toString().trim();
  const email = formData.get('email')?.toString().trim();
  const phoneNumber = formData.get('phone_number')?.toString().trim() || null;
  const privacy = (form.querySelector('input[name="privacy"]') as HTMLInputElement)?.checked;
  const eventId = formData.get('event_id')?.toString();

  // Validation
  if (!firstName || !lastName) {
    showMessage(messageContainer, 'Bitte fülle alle Pflichtfelder aus.', 'error');

    return;
  }

  if (!email || !validateEmail(email)) {
    showMessage(messageContainer, 'Bitte gib eine gültige E-Mail-Adresse ein.', 'error');

    return;
  }

  if (!privacy) {
    showMessage(messageContainer, 'Bitte bestätige die Datenschutzerklärung.', 'error');

    return;
  }

  // Disable button during submission
  submitButton.disabled = true;
  submitButton.textContent = 'Wird gesendet...';

  const data: EventRegistrationData = {
    event_id: eventId || '',
    first_name: firstName,
    last_name: lastName,
    email,
    phone_number: phoneNumber,
    privacy: privacy ? 1 : 0,
  };

  // Get API endpoint and CSRF token
  const apiUrl = getApiEndpoint('eventRegister');
  const csrfToken = getCsrfToken();

  if (!apiUrl) {
    showMessage(messageContainer, 'Konfigurationsfehler. Bitte kontaktiere den Administrator.', 'error');
    submitButton.disabled = false;
    submitButton.textContent = 'Verbindlich anmelden';

    return;
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
        showMessage(messageContainer, data.message, 'success');
        form.reset();
      } else {
        showMessage(messageContainer, data.message || 'Ein Fehler ist aufgetreten.', 'error');
      }
    })
    .catch(() => {
      showMessage(messageContainer, 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.', 'error');
    })
    .finally(() => {
      submitButton.disabled = false;
      submitButton.textContent = 'Verbindlich anmelden';
    });
}
