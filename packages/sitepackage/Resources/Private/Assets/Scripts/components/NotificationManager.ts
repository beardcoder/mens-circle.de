import type {
  NotificationType,
  NotificationOptions,
  NotificationManagerFactory,
} from "../types/index";

/**
 * NotificationManager Factory
 * Creates a central notification system for user feedback
 * Uses CSS classes from notifications.css for styling
 */
export const createNotificationManager = (): NotificationManagerFactory => {
  // Constants
  const DEFAULT_DURATIONS: Record<NotificationType, number> = {
    success: 8000,
    error: 10000,
    info: 7000,
  };

  // Ensure notification container exists
  let container: HTMLElement | null = null;

  const ensureContainer = (): HTMLElement => {
    if (!container) {
      container = document.getElementById("notification-container");
      
      if (!container) {
        container = document.createElement("div");
        container.id = "notification-container";
        container.className = "notification-container";
        container.setAttribute("role", "region");
        container.setAttribute("aria-label", "Benachrichtigungen");
        container.setAttribute("aria-live", "polite");
        document.body.appendChild(container);
      }
    }
    
    return container;
  };

  // Private functions
  const getIcon = (type: NotificationType): string => {
    const icons: Record<NotificationType, string> = {
      success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
      error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
      info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };
    return icons[type] || icons.info;
  };

  const createNotificationElement = (
    message: string,
    type: NotificationType,
  ): HTMLDivElement => {
    const notification = document.createElement("div");
    notification.className = `notification notification--${type}`;
    notification.setAttribute("role", "alert");
    notification.setAttribute("aria-atomic", "true");

    // Icon
    const iconElement = document.createElement("span");
    iconElement.className = "notification__icon";
    iconElement.setAttribute("aria-hidden", "true");
    iconElement.innerHTML = getIcon(type);

    // Message
    const messageElement = document.createElement("span");
    messageElement.className = "notification__message";
    messageElement.textContent = message;

    // Close button
    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.className = "notification__close";
    closeButton.setAttribute("aria-label", "Benachrichtigung schließen");
    closeButton.innerHTML = "&times;";
    closeButton.addEventListener("click", () => dismiss(notification));

    notification.appendChild(iconElement);
    notification.appendChild(messageElement);
    notification.appendChild(closeButton);

    return notification;
  };

  const dismiss = (notification: HTMLElement): void => {
    notification.classList.remove("show");
    notification.classList.add("hide");

    notification.addEventListener(
      "transitionend",
      () => {
        notification.remove();
      },
      { once: true }
    );
  };

  // Public API
  const show = (options: NotificationOptions): void => {
    const { message, type = "info", duration } = options;

    const notificationContainer = ensureContainer();
    const notification = createNotificationElement(message, type);
    
    notificationContainer.appendChild(notification);

    // Trigger animation
    requestAnimationFrame(() => {
      notification.classList.add("show");
    });

    // Auto dismiss after duration
    const displayDuration = duration ?? DEFAULT_DURATIONS[type];
    if (displayDuration > 0) {
      setTimeout(() => {
        dismiss(notification);
      }, displayDuration);
    }
  };

  const success = (message: string): void => {
    show({ message, type: "success" });
  };

  const error = (message: string): void => {
    show({ message, type: "error" });
  };

  const info = (message: string): void => {
    show({ message, type: "info" });
  };

  const destroy = (): void => {
    if (container) {
      container.remove();
      container = null;
    }
  };

  return {
    show,
    success,
    error,
    info,
    destroy,
    [Symbol.dispose]: destroy,
  };
};
