import { createNotificationManager } from "./NotificationManager";
import { CONFIG } from "../config/index";
import type {
  FormFactory,
  NotificationManagerFactory,
} from "../types/index";

/**
 * Shared form validation and helper functions
 */
const validateField = (
  field: HTMLInputElement | HTMLTextAreaElement,
): boolean => {
  const value = field.value.trim();
  let isValid = true;
  let errorMessage = "";

  if (field.hasAttribute("required") && !value) {
    isValid = false;
    errorMessage = "Dieses Feld ist erforderlich";
  } else if (field.type === "email" && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      isValid = false;
      errorMessage = "Bitte gib eine gültige E-Mail-Adresse ein";
    }
  }

  showFieldError(field, isValid, errorMessage);
  return isValid;
};

const showFieldError = (
  field: HTMLElement,
  isValid: boolean,
  message: string,
): void => {
  const existingError = field.parentElement?.querySelector(".form__error");
  if (existingError) {
    existingError.remove();
  }

  if (!isValid) {
    (field as HTMLInputElement).style.borderColor = "#d32f2f";
    const errorEl = document.createElement("span");
    errorEl.className = "form__error";
    errorEl.textContent = message;
    errorEl.style.cssText =
      "display: block; color: #d32f2f; font-size: 0.875rem; margin-top: 0.25rem;";
    field.parentElement?.appendChild(errorEl);
  } else {
    (field as HTMLInputElement).style.borderColor = "";
  }
};

const validateFormFields = (form: HTMLFormElement): boolean => {
  const inputs = form.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
    "input[required], textarea[required]",
  );
  let isFormValid = true;

  inputs.forEach((input) => {
    if (!validateField(input)) {
      isFormValid = false;
    }
  });

  return isFormValid;
};

const submitForm = async (
  form: HTMLFormElement,
): Promise<Record<string, unknown>> => {
  const response = await fetch(form.action, {
    method: form.method || "POST",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: new FormData(form),
  });

  const payload = (await response
    .clone()
    .json()
    .catch(() => ({}))) as Record<string, unknown>;

  if (!response.ok) {
    const errorMessage = (payload?.message as string) ??
      "Beim Senden ist ein Fehler aufgetreten.";
    const error = new Error(errorMessage);
    (error as Error & { details?: Record<string, unknown> }).details = payload;
    throw error;
  }

  if (CONFIG.development) {
    // eslint-disable-next-line no-console
    console.info("Form submit success", payload);
  }

  return payload;
};

/**
 * ContactForm Factory
 * Creates a contact form handler with validation
 */
export const createContactForm = (
  notificationManager?: NotificationManagerFactory,
): FormFactory | null => {
  const form = document.getElementById("contactForm") as HTMLFormElement | null;
  if (!form) return null;

  const notifications = notificationManager ?? createNotificationManager();

  const handleSubmit = async (e: Event): Promise<void> => {
    e.preventDefault();

    if (!validateFormFields(form)) {
      notifications.error("Bitte füllen Sie alle Pflichtfelder korrekt aus.");
      return;
    }

    const submitButton = form.querySelector<HTMLButtonElement>(
      'button[type="submit"]',
    );
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Wird gesendet...";
    }

    try {
      const result = await submitForm(form);
      notifications.success(
        (result?.message as string) ??
          "Vielen Dank für deine Nachricht! Wir melden uns bald bei dir.",
      );
      form.reset();
    } catch (error) {
      notifications.error(
        error instanceof Error
          ? error.message
          : "Beim Senden ist ein Fehler aufgetreten. Bitte versuche es später erneut.",
      );
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = "Nachricht senden";
      }
    }
  };

  const handleBlur = (input: HTMLInputElement | HTMLTextAreaElement) => () => {
    validateField(input);
  };

  const init = (): void => {
    form.addEventListener("submit", handleSubmit);

    const inputs = form.querySelectorAll<
      HTMLInputElement | HTMLTextAreaElement
    >("input, textarea");
    inputs.forEach((input) => {
      input.addEventListener("blur", handleBlur(input));
    });
  };

  const reset = (): void => {
    form.reset();
    form.querySelectorAll(".form__error").forEach((err) => err.remove());
    form
      .querySelectorAll<HTMLInputElement>("input, textarea")
      .forEach((input) => {
        input.style.borderColor = "";
      });
  };

  const validate = (): boolean => {
    return validateFormFields(form);
  };

  const destroy = (): void => {
    form.removeEventListener("submit", handleSubmit);
    const inputs = form.querySelectorAll<
      HTMLInputElement | HTMLTextAreaElement
    >("input, textarea");
    inputs.forEach((input) => {
      input.removeEventListener("blur", handleBlur(input));
    });
  };

  init();

  return {
    reset,
    validate,
    destroy,
    [Symbol.dispose]: destroy,
  };
};

/**
 * NewsletterForm Factory
 * Creates a newsletter subscription form handler
 */
export const createNewsletterForm = (
  notificationManager?: NotificationManagerFactory,
): FormFactory | null => {
  const form = document.getElementById(
    "newsletterForm",
  ) as HTMLFormElement | null;
  if (!form) return null;

  const notifications = notificationManager ?? createNotificationManager();

  const handleSubmit = async (e: Event): Promise<void> => {
    e.preventDefault();

    if (!validateFormFields(form)) {
      notifications.error("Bitte gib eine gültige E-Mail-Adresse ein.");
      return;
    }

    const submitButton = form.querySelector<HTMLButtonElement>(
      'button[type="submit"]',
    );
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Wird angemeldet...";
    }

    try {
      const result = await submitForm(form);
      notifications.success(
        (result?.message as string) ??
          "Erfolgreich für den Newsletter angemeldet!",
      );
      form.reset();
    } catch (error) {
      notifications.error(
        error instanceof Error
          ? error.message
          : "Anmeldung fehlgeschlagen. Bitte versuche es später erneut.",
      );
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = "Anmelden";
      }
    }
  };

  const handleBlur = (input: HTMLInputElement) => () => {
    validateField(input);
  };

  const init = (): void => {
    form.addEventListener("submit", handleSubmit);

    const inputs = form.querySelectorAll<HTMLInputElement>("input");
    inputs.forEach((input) => {
      input.addEventListener("blur", handleBlur(input));
    });
  };

  const reset = (): void => {
    form.reset();
    form.querySelectorAll(".form__error").forEach((err) => err.remove());
    form.querySelectorAll<HTMLInputElement>("input").forEach((input) => {
      input.style.borderColor = "";
    });
  };

  const validate = (): boolean => {
    return validateFormFields(form);
  };

  const destroy = (): void => {
    form.removeEventListener("submit", handleSubmit);
    const inputs = form.querySelectorAll<HTMLInputElement>("input");
    inputs.forEach((input) => {
      input.removeEventListener("blur", handleBlur(input));
    });
  };

  init();

  return {
    reset,
    validate,
    destroy,
    [Symbol.dispose]: destroy,
  };
};

/**
 * RegistrationForm Factory
 * Creates a registration form handler with enhanced validation
 */
export const createRegistrationForm = (
  notificationManager?: NotificationManagerFactory,
): FormFactory | null => {
  const form = document.getElementById(
    "registrationForm",
  ) as HTMLFormElement | null;
  if (!form) return null;

  const notifications = notificationManager ?? createNotificationManager();

  const handleSubmit = async (e: Event): Promise<void> => {
    e.preventDefault();

    if (!validateFormFields(form)) {
      notifications.error("Bitte füllen Sie alle Pflichtfelder korrekt aus.");
      return;
    }

    const submitButton = form.querySelector<HTMLButtonElement>(
      'button[type="submit"]',
    );
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = "Wird registriert...";
    }

    try {
      const result = await submitForm(form);
      notifications.success(
        (result?.message as string) ??
          "🎉 Platz reserviert! Du erhältst in Kürze eine Bestätigungsmail.",
      );
      form.reset();

      // Redirect after success
      setTimeout(() => {
        window.location.href = "/";
      }, 3000);
    } catch (error) {
      notifications.error(
        error instanceof Error
          ? error.message
          : "Registrierung fehlgeschlagen. Bitte versuche es später erneut.",
      );
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = "Jetzt anmelden";
      }
    }
  };

  const handleBlur = (input: HTMLInputElement | HTMLTextAreaElement) => () => {
    validateField(input);
  };

  const init = (): void => {
    form.addEventListener("submit", handleSubmit);

    const inputs = form.querySelectorAll<
      HTMLInputElement | HTMLTextAreaElement
    >("input, textarea, select");
    inputs.forEach((input) => {
      input.addEventListener("blur", handleBlur(input));
    });
  };

  const reset = (): void => {
    form.reset();
    form.querySelectorAll(".form__error").forEach((err) => err.remove());
    form
      .querySelectorAll<HTMLInputElement>("input, textarea, select")
      .forEach((input) => {
        input.style.borderColor = "";
      });
  };

  const validate = (): boolean => {
    return validateFormFields(form);
  };

  const destroy = (): void => {
    form.removeEventListener("submit", handleSubmit);
    const inputs = form.querySelectorAll<
      HTMLInputElement | HTMLTextAreaElement
    >("input, textarea, select");
    inputs.forEach((input) => {
      input.removeEventListener("blur", handleBlur(input));
    });
  };

  init();

  return {
    reset,
    validate,
    destroy,
    [Symbol.dispose]: destroy,
  };
};
