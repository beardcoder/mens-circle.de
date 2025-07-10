import '@fontsource-variable/playfair-display/wght-italic.css';
import '@fontsource-variable/jost';
import { animate, inView } from 'motion';
import "@hotwired/turbo";

const init = () => {
  inView('[data-animate="fadeUp"]', (element) => {

    const delay = (element as HTMLElement).dataset?.delay ?? 0;
    const duration = (element as HTMLElement).dataset?.duration ?? 0.5;
    animate(
      element,
      { opacity: 1, y: [ 40, 0 ] },
      {
        delay: delay as number,
        duration: duration as number,
      },
    );
  });

  inView('[data-animate="fadeDown"]', (element) => {
    const delay = (element as HTMLElement).dataset?.delay ?? 0;
    const duration = (element as HTMLElement).dataset?.duration ?? 0.5;
    animate(
      element,
      { opacity: 1, y: [ -40, 0 ] },
      {
        delay: delay as number,
        duration: duration as number,
      },
    );
  });
}

//document.addEventListener('DOMContentLoaded', init);
document.addEventListener('turbo:load', init);
