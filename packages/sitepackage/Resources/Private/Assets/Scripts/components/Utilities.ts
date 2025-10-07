import type {
  LazyLoaderFactory,
  FAQAccordionFactory,
  PerformanceMonitorFactory,
  ObserverOptions,
} from "../types/index";
import { CONFIG } from "../config/index";

/**
 * LazyImageLoader Factory
 * Creates a lazy image loader using Intersection Observer
 */
export const createLazyImageLoader = (): LazyLoaderFactory => {
  const images = document.querySelectorAll<HTMLImageElement>("img[data-src]");
  let observer: IntersectionObserver | null = null;

  const observerOptions: ObserverOptions = {
    root: null,
    threshold: 0.1,
    rootMargin: "50px",
  };

  const loadImage = (img: HTMLImageElement): void => {
    const src = img.dataset.src;
    if (!src) return;

    img.src = src;
    img.removeAttribute("data-src");
    img.classList.add("loaded");
  };

  const handleIntersection = (entries: IntersectionObserverEntry[]): void => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target as HTMLImageElement;
        loadImage(img);
        observer?.unobserve(img);
      }
    });
  };

  const init = (): void => {
    if (images.length === 0) return;

    if ("IntersectionObserver" in window) {
      observer = new IntersectionObserver(handleIntersection, observerOptions);
      images.forEach((img) => observer?.observe(img));
    } else {
      // Fallback: Load all images immediately
      images.forEach((img) => loadImage(img));
    }
  };

  const destroy = (): void => {
    if (observer) {
      observer.disconnect();
      observer = null;
    }
  };

  init();

  return {
    loadImage,
    destroy,
    [Symbol.dispose]: destroy,
  };
};

/**
 * FAQAccordion Factory
 * Creates an FAQ accordion with expand/collapse functionality
 */
export const createFAQAccordion = (): FAQAccordionFactory => {
  const faqItems = document.querySelectorAll<HTMLElement>(".faq__item");

  const handleClick = (item: HTMLElement) => (e: Event) => {
    const button = e.target as HTMLElement;
    if (!button.classList.contains("faq__question")) return;

    const isActive = item.classList.contains("is-active");

    // Close all other items (optional - remove for multi-open)
    faqItems.forEach((otherItem) => {
      if (otherItem !== item) {
        otherItem.classList.remove("is-active");
        const otherAnswer =
          otherItem.querySelector<HTMLElement>(".faq__answer");
        if (otherAnswer) {
          otherAnswer.style.maxHeight = "0";
        }
      }
    });

    // Toggle current item
    item.classList.toggle("is-active");
    const answer = item.querySelector<HTMLElement>(".faq__answer");
    if (answer) {
      if (isActive) {
        answer.style.maxHeight = "0";
      } else {
        answer.style.maxHeight = `${answer.scrollHeight}px`;
      }
    }
  };

  const init = (): void => {
    faqItems.forEach((item) => {
      const question = item.querySelector(".faq__question");
      if (question) {
        question.addEventListener("click", handleClick(item));
      }
    });
  };

  const openItem = (index: number): void => {
    const item = faqItems[index];
    if (!item) return;

    item.classList.add("is-active");
    const answer = item.querySelector<HTMLElement>(".faq__answer");
    if (answer) {
      answer.style.maxHeight = `${answer.scrollHeight}px`;
    }
  };

  const closeAll = (): void => {
    faqItems.forEach((item) => {
      item.classList.remove("is-active");
      const answer = item.querySelector<HTMLElement>(".faq__answer");
      if (answer) {
        answer.style.maxHeight = "0";
      }
    });
  };

  const destroy = (): void => {
    faqItems.forEach((item) => {
      const question = item.querySelector(".faq__question");
      if (question) {
        question.removeEventListener("click", handleClick(item));
      }
    });
  };

  init();

  return {
    openItem,
    closeAll,
    destroy,
    [Symbol.dispose]: destroy,
  };
};

/**
 * PerformanceMonitor Factory
 * Monitors and logs performance metrics (development only)
 */
export const createPerformanceMonitor = (): PerformanceMonitorFactory => {
  if (!CONFIG.development) {
    const noop = () => {};
    return {
      log: noop,
      destroy: noop,
      [Symbol.dispose]: noop,
    };
  }

  const metrics = {
    lcp: 0,
    fid: 0,
    cls: 0,
  };

  const logMetric = (name: string, value: number): void => {
    // eslint-disable-next-line no-console
    console.info(`[Performance] ${name}:`, value.toFixed(2));
  };

  const observeLCP = (): void => {
    if (!("PerformanceObserver" in window)) return;

    const observer = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      const lastEntry = entries[entries.length - 1] as any;
      metrics.lcp = lastEntry.renderTime || lastEntry.loadTime;
      logMetric("LCP (Largest Contentful Paint)", metrics.lcp);
    });

    observer.observe({ entryTypes: ["largest-contentful-paint"] });
  };

  const observeFID = (): void => {
    if (!("PerformanceObserver" in window)) return;

    const observer = new PerformanceObserver((list) => {
      list.getEntries().forEach((entry: any) => {
        metrics.fid = entry.processingStart - entry.startTime;
        logMetric("FID (First Input Delay)", metrics.fid);
      });
    });

    observer.observe({ entryTypes: ["first-input"] });
  };

  const observeCLS = (): void => {
    if (!("PerformanceObserver" in window)) return;

    const observer = new PerformanceObserver((list) => {
      list.getEntries().forEach((entry: any) => {
        if (!entry.hadRecentInput) {
          metrics.cls += entry.value;
          logMetric("CLS (Cumulative Layout Shift)", metrics.cls);
        }
      });
    });

    observer.observe({ entryTypes: ["layout-shift"] });
  };

  const init = (): void => {
    observeLCP();
    observeFID();
    observeCLS();
  };

  const log = (): void => {
    // eslint-disable-next-line no-console
    console.table(metrics);
  };

  const destroy = (): void => {
    // Performance Observers are automatically disconnected on page unload
  };

  init();

  return {
    log,
    destroy,
    [Symbol.dispose]: destroy,
  };
};
