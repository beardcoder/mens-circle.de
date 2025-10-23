import { CONFIG } from "./config";
import { createNavigation } from "./components/Navigation";
import { createSmoothScroll } from "./components/SmoothScroll";
import { createNotificationManager } from "./components/NotificationManager";
import { createViewTransitions } from "./components/ViewTransitions";
import type { Factory } from "./types";

const factories: Factory[] = [];

const registerFactory = (factory?: Factory | null): void => {
  if (factory) {
    factories.push(factory);
  }
};

const loadUtilities = (() => {
  let promise: Promise<typeof import("./components/Utilities")> | null = null;

  return () => {
    if (!promise) {
      promise = import("./components/Utilities");
    }

    return promise;
  };
})();

let notificationsInstance = null as ReturnType<typeof createNotificationManager> | null;

const getNotifications = () => {
  if (!notificationsInstance) {
    notificationsInstance = createNotificationManager();
  }

  return notificationsInstance;
};

const bootstrap = async (): Promise<void> => {
  // Initialize View Transitions API (2025)
  registerFactory(createViewTransitions());

  registerFactory(createNavigation());
  registerFactory(createSmoothScroll());

  if (document.querySelector("img[data-src]")) {
    const { createLazyImageLoader } = await loadUtilities();
    registerFactory(createLazyImageLoader());
  }

  if (document.querySelector(".reveal-card, .reveal-text")) {
    const { createScrollReveal } = await import("./components/ScrollReveal");
    registerFactory(createScrollReveal());
  }

  if (document.querySelector(".faq__item")) {
    const { createFAQAccordion } = await loadUtilities();
    registerFactory(createFAQAccordion());
  }


  if (CONFIG.development) {
    const { createPerformanceMonitor } = await loadUtilities();
    registerFactory(createPerformanceMonitor());
  }

  document.body.classList.add("is-loaded");
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    void bootstrap();
  });
} else {
  void bootstrap();
}

if (CONFIG.development) {
  (window as any).disposeApp = () => {
    factories.forEach((factory) => factory[Symbol.dispose]?.());
    factories.length = 0;
  };
}
