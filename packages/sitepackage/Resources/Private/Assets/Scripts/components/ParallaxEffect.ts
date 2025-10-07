import { CONFIG } from "../config/index";
import { throttle } from "../utils/functions";
import type { ParallaxFactory } from "../types/index";

/**
 * ParallaxEffect Factory
 * Creates parallax scrolling effects
 */
export const createParallaxEffect = (): ParallaxFactory => {
  // State
  const elements = document.querySelectorAll<HTMLElement>("[data-parallax]");

  // Private functions
  const handleScroll = (): void => {
    const scrolled = window.scrollY;

    elements.forEach((element) => {
      const speed = parseFloat(element.dataset.parallax ?? "0.5");
      const yPos = -(scrolled * speed);
      element.style.transform = `translateY(${yPos}px)`;
    });
  };

  const throttledScroll = throttle(handleScroll, 10);

  // Initialize
  const init = (): void => {
    if (elements.length === 0) return;
    if (CONFIG.animations.reducedMotion) return;

    window.addEventListener("scroll", throttledScroll);
  };

  // Public API
  const update = (): void => {
    handleScroll();
  };

  const destroy = (): void => {
    window.removeEventListener("scroll", throttledScroll);

    // Reset transforms
    elements.forEach((element) => {
      element.style.transform = "";
    });
  };

  // Initialize and return public API with ES2023 Resource Management
  init();

  return {
    update,
    destroy,
    [Symbol.dispose]: destroy,
  };
};
