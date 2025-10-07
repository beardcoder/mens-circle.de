import { throttle } from "../utils/functions";
import type { SmoothScrollFactory } from "../types/index";

/**
 * SmoothScroll Factory
 * Creates smooth scroll with active link highlighting
 */
export const createSmoothScroll = (): SmoothScrollFactory => {
  // State
  const links = document.querySelectorAll<HTMLAnchorElement>('a[href^="#"]');
  const sections = document.querySelectorAll<HTMLElement>("section[id]");
  const navHeight = 80;

  // Private functions
  const handleClick = (e: MouseEvent): void => {
    const link = e.currentTarget as HTMLAnchorElement;
    const href = link.getAttribute("href");

    if (!href || !href.startsWith("#")) return;

    const targetId = href.substring(1);
    if (!targetId) return;

    const targetElement = document.getElementById(targetId);
    if (!targetElement) return;

    e.preventDefault();

    const targetPosition = targetElement.offsetTop - navHeight;

    window.scrollTo({
      top: targetPosition,
      behavior: "smooth",
    });
  };

  const updateActiveLink = (): void => {
    let currentSection = "";
    const scrollPosition = window.scrollY + navHeight + 100;

    sections.forEach((section) => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      if (
        scrollPosition >= sectionTop &&
        scrollPosition < sectionTop + sectionHeight
      ) {
        currentSection = section.getAttribute("id") ?? "";
      }
    });

    links.forEach((link) => {
      link.classList.remove("is-active");
      if (link.getAttribute("href") === `#${currentSection}`) {
        link.classList.add("is-active");
      }
    });
  };

  const throttledUpdateActiveLink = throttle(updateActiveLink, 100);

  // Initialize
  const init = (): void => {
    links.forEach((link) => {
      link.addEventListener("click", handleClick);
    });

    if (sections.length > 0) {
      window.addEventListener("scroll", throttledUpdateActiveLink);
    }
  };

  // Public API
  const scrollTo = (target: string | Element): void => {
    const targetElement =
      typeof target === "string" ? document.querySelector(target) : target;

    if (!targetElement) return;

    const targetPosition = (targetElement as HTMLElement).offsetTop - navHeight;

    window.scrollTo({
      top: targetPosition,
      behavior: "smooth",
    });
  };

  const destroy = (): void => {
    links.forEach((link) => {
      link.removeEventListener("click", handleClick);
    });

    window.removeEventListener("scroll", throttledUpdateActiveLink);
  };

  // Initialize and return public API with ES2023 Resource Management
  init();

  return {
    scrollTo,
    destroy,
    [Symbol.dispose]: destroy,
  };
};
