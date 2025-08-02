// Main API for @fullhaus/component-api

/** Component cleanup function */
export type CleanupFunction = () => void;

/** Reference hook return type */
export type RefHook<T extends Element> = T | null;

/**
 * Component instance interface that provides access to the DOM element and helper methods
 */
export interface ComponentInstance<
  T extends HTMLElement = HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
> {
  /** The DOM element this component is bound to */
  readonly element: T;
  /** Find a child element by selector within this component */
  querySelector<E extends Element = Element>(selector: string): E | null;
  /** Find all child elements by selector within this component */
  querySelectorAll<E extends Element = Element>(
    selector: string,
  ): NodeListOf<E>;
  /** Props extracted from data-props-* attributes (reactive if enabled) */
  readonly props: TProps;
  /** useRef hook for referencing DOM elements by ref attribute */
  useRef: <TElement extends Element = Element>(
    refName: string,
  ) => RefHook<TElement>;
}

/**
 * Component initialization callback function
 */
export type ComponentCallback<
  T extends HTMLElement = HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
> = (component: ComponentInstance<T, TProps>) => CleanupFunction | undefined;

/**
 * Component configuration object
 */
export interface ComponentConfig<
  T extends HTMLElement = HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
> {
  readonly selector: string;
  readonly callback: ComponentCallback<T, TProps>;
  readonly reactive?: boolean;
}

/**
 * Converts kebab-case to camelCase
 */
function kebabToCamelCase(str: string): string {
  return str.replace(/-([a-z])/g, (_, letter: string) => letter.toUpperCase());
}

/**
 * Safely parses a string value to its appropriate type
 */
export function parseValue(value: string): unknown {
  if (value === 'true') {
    return true;
  }
  if (value === 'false') {
    return false;
  }
  if (value === '') {
    return '';
  }
  const numValue = Number(value);
  if (!Number.isNaN(numValue)) {
    return numValue;
  }
  if (value.startsWith('{') || value.startsWith('[')) {
    try {
      return JSON.parse(value);
    } catch {
      // Ignore JSON parsing errors, return as string
    }
  }
  return value;
}

function isTurboAvailable(): boolean {
  return (
    typeof window !== 'undefined' &&
    'Turbo' in window &&
    typeof (window as { Turbo?: { navigator?: object } }).Turbo === 'object'
  );
}

type SignalSubscriber<T> = (value: T) => void;

export interface Signal<T> {
  value: T;
  subscribe(fn: SignalSubscriber<T>): () => void;
}

export function useSignal<T>(initial: T): Signal<T> {
  let _value = initial;
  const subscribers = new Set<SignalSubscriber<T>>();
  return {
    subscribe(fn: SignalSubscriber<T>) {
      subscribers.add(fn);
      return () => subscribers.delete(fn);
    },
    get value(): T {
      return _value;
    },
    set value(next) {
      if (!Object.is(_value, next)) {
        _value = next;
        subscribers.forEach((fn) => fn(_value));
      }
    },
  };
}

export function useEffect(
  callback: () => (() => void) | undefined,
  ...signals: Signal<unknown>[]
): () => void {
  let cleanup: (() => void) | undefined;

  const run = (): void => {
    if (typeof cleanup === 'function') {
      cleanup();
    }
    cleanup = callback() ?? undefined;
  };

  const unsubs = signals.map((sig) => sig.subscribe(run));

  run();

  return () => {
    if (typeof cleanup === 'function') {
      cleanup();
    }
    unsubs.forEach((unsub) => unsub());
  };
}

function extractProps(element: HTMLElement): Record<string, unknown> {
  const DATA_PROPS_PREFIX = 'data-props-';
  const props: Record<string, unknown> = {};
  const attributes = element.attributes;
  const prefixLength = DATA_PROPS_PREFIX.length;
  for (const attr of attributes) {
    if (attr.name.startsWith(DATA_PROPS_PREFIX)) {
      const propName = kebabToCamelCase(attr.name.slice(prefixLength));
      props[propName] = parseValue(attr.value);
    }
  }
  return props;
}

function createRefHook<T extends HTMLElement>(
  element: T,
): <TElement extends Element = Element>(refName: string) => RefHook<TElement> {
  return <TElement extends Element = Element>(
    refName: string,
  ): RefHook<TElement> => {
    return element.querySelector<TElement>(`[ref="${refName}"]`);
  };
}

export function useRef<TElement extends Element = Element>(
  refName: string,
  container: Element = document.body,
): RefHook<TElement> {
  return container.querySelector<TElement>(`[ref="${refName}"]`);
}

export function useDocumentLanguage(): string {
  const htmlElement = document.documentElement;
  return htmlElement.lang || 'en';
}

export function isEmpty(str: string): boolean {
  return !str || str.length === 0;
}

function createComponentInstance<
  T extends HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
>(element: T): ComponentInstance<T, TProps> {
  const props = extractProps(element) as TProps;
  const querySelector = element.querySelector.bind(element);
  const querySelectorAll = element.querySelectorAll.bind(element);
  const useRef = createRefHook(element);
  return {
    element,
    props,
    querySelector,
    querySelectorAll,
    useRef,
  };
}

/**
 * Initialize components with proper error handling and cleanup
 */
export function initializeComponents<
  T extends HTMLElement = HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
>(selector: string, callback: ComponentCallback<T, TProps>): CleanupFunction[] {
  const elements = document.querySelectorAll<T>(selector);
  const cleanupFunctions: CleanupFunction[] = [];
  for (const element of elements) {
    try {
      const componentInstance = createComponentInstance<T, TProps>(element);
      callback(componentInstance);
    } catch (error) {
      // eslint-disable-next-line no-console -- Log errors during component initialization
      console.error(`Error initializing component for selector "${selector}":`, error);
    }
  }
  return cleanupFunctions;
}

export function createComponent<
  T extends HTMLElement = HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
>(
  selector: string,
  callback: ComponentCallback<T, TProps>,
): ComponentConfig<T, TProps> {
  if (isEmpty(selector)) {
    throw new Error('Component selector must be a non-empty string');
  }
  if (typeof callback !== 'function') {
    throw new Error('Component callback must be a function');
  }
  return {
    callback,
    selector,
  };
}

/**
 * Mount a component with automatic DOM ready and Turbo support
 */
export function mount<
  T extends HTMLElement = HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
>(component: ComponentConfig<T, TProps>): Promise<CleanupFunction[]> {
  const { selector, callback } = component;
  let cleanupFunctions: CleanupFunction[] = [];
  let isMounted = false;
  return new Promise((resolve) => {
    const initialize = (): void => {
      if( isMounted ) {
        return;
      }
      cleanupFunctions = initializeComponents<T, TProps>(selector, callback);
      isMounted = true;
      resolve(cleanupFunctions);
    };
    const cleanup = (): void => {
      if (!isMounted) {
        return;
      }
      isMounted = false;
      cleanupFunctions.forEach((fn) => {
        try {
          fn();
        } catch (err) {
          // eslint-disable-next-line no-console -- Log errors during cleanup
          console.warn(`Error during cleanup for selector "${selector}":`, err);
        }
      });
      cleanupFunctions = [];
    };
    if (isTurboAvailable()) {
      document.addEventListener('turbo:before-render', cleanup, {
        passive: true,
      });
      document.addEventListener('turbo:load', initialize, { passive: true });
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initialize, {
        once: true,
        passive: true,
      });
    } else {
      setTimeout(initialize, 0);
    }
  });
}

/**
 * Create and mount a component in one step
 */
export function createComponentAndMount<
  T extends HTMLElement = HTMLElement,
  TProps extends Record<string, unknown> = Record<string, unknown>,
>(
  selector: string,
  callback: ComponentCallback<T, TProps>,
): Promise<CleanupFunction[]> {
  const component = createComponent<T, TProps>(selector, callback);
  return mount(component);
}

/**
 * Create a single component instance for the first matching element
 */
export function createSingleComponent<T extends HTMLElement = HTMLElement>(
  selector: string,
  callback: ComponentCallback<T>,
): CleanupFunction | undefined {
  const element = document.querySelector<T>(selector);
  if (!element) {
    // eslint-disable-next-line no-console -- Log warning if no element found
    console.warn(`No element found for selector: ${selector}`);
    return;
  }
  const componentInstance = createComponentInstance(element);
  const result = callback(componentInstance);
  return typeof result === 'function' ? result : undefined;
}
