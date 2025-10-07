import type {
  NotificationType,
  NotificationOptions,
  NotificationManagerFactory,
} from "../types/index";

/**
 * NotificationManager Factory
 * Creates a central notification system for user feedback
 */
export const createNotificationManager = (): NotificationManagerFactory => {
  // Constants
  const COLORS: Record<NotificationType, string> = {
    success: "#4caf50",
    error: "#d32f2f",
    info: "#2196f3",
  };

  const DEFAULT_DURATIONS: Record<NotificationType, number> = {
    success: 6000,
    error: 5000,
    info: 5000,
  };

  // Private functions
  const removeExisting = (): void => {
    const existing = document.querySelector(".notification");
    if (existing) {
      existing.remove();
    }
  };

  const createNotificationElement = (
    message: string,
    type: NotificationType,
  ): HTMLDivElement => {
    const notification = document.createElement("div");
    notification.className = "notification";
    notification.textContent = message;
    notification.setAttribute("role", "alert");
    notification.setAttribute("aria-live", "polite");

    notification.style.cssText = `
      position: fixed;
      top: 6rem;
      right: 2rem;
      max-width: 400px;
      padding: 1rem 1.5rem;
      background: ${COLORS[type]};
      color: white;
      border-radius: 0.5rem;
      box-shadow: 0 4px 16px rgba(0,0,0,0.2);
      z-index: 10000;
      animation: slideIn 0.3s ease-out;
    `;

    return notification;
  };

  // Public API
  const show = (options: NotificationOptions): void => {
    const { message, type = "info", duration } = options;

    removeExisting();

    const notification = createNotificationElement(message, type);
    document.body.appendChild(notification);

    // Auto remove after duration
    const displayDuration = duration ?? DEFAULT_DURATIONS[type];
    setTimeout(() => {
      notification.style.animation = "slideOut 0.3s ease-out";
      setTimeout(() => notification.remove(), 300);
    }, displayDuration);
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
    removeExisting();
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
