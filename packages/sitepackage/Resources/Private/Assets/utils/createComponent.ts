/**
 * AST node representing an element or text node.
 */
export interface ComponentNode {
    type: 'element' | 'text'
    tagName?: string
    attributes?: Record<string, string>
    children?: ComponentNode[]
    textContent?: string
}

/**
 * Configuration options for component creation.
 */
export interface ComponentOptions<TProps = Record<string, any>> {
    /** Whether to enable reactive props */
    reactive?: boolean
    /** Custom event handlers to attach */
    eventHandlers?: Record<string, EventListener>
    /** Initial prop values to override */
    initialProps?: Partial<TProps>
}

/**
 * Public API returned by createComponent factory.
 */
export interface ComponentElement<
    TElement extends HTMLElement = HTMLElement,
    TProps extends Record<string, any> = Record<string, any>,
> {
    /** Root HTML element */
    readonly element: TElement
    /** Original inner HTML */
    readonly html: string
    /** AST representation of child nodes */
    readonly ast: ComponentNode[]
    /** Reactive props synced with data-prop-* attributes */
    readonly props: TProps
    /** Update a prop value */
    updateProp<K extends keyof TProps>(key: K, value: TProps[K]): void
    /** Get current prop value */
    getProp<K extends keyof TProps>(key: K): TProps[K]
    /** Check if prop exists */
    hasProp(key: keyof TProps): boolean
    /** Get all prop keys */
    getPropKeys(): Array<keyof TProps>
    /** Find a descendant */
    querySelector<T extends HTMLElement = HTMLElement>(selector: string): T | null
    /** Find all matching descendants */
    querySelectorAll<T extends HTMLElement = HTMLElement>(selector: string): NodeListOf<T>
    /** Attach event listener */
    on(event: string, handler: EventListener): void
    /** Remove event listener */
    off(event: string, handler: EventListener): void
    /** Destroy component and cleanup */
    destroy(): void
}

// ---------------------------------------------------------------------------
// Internal utilities (simplified)
// ---------------------------------------------------------------------------

/**
 * Parse string value to appropriate type.
 */
const parseValue = (value: string): any => {
    if (value === 'true') return true
    if (value === 'false') return false
    if (!isNaN(Number(value)) && value.trim() !== '') return Number(value)
    try {
        return JSON.parse(value)
    } catch {
        return value
    }
}

/**
 * Extract props from data-prop-* attributes.
 */
const extractProps = <TProps extends Record<string, any>>(el: HTMLElement): Partial<TProps> => {
    const result: Record<string, any> = {}
    Array.from(el.attributes).forEach(({ name, value }) => {
        if (name.startsWith('data-prop-')) {
            const key = name.slice('data-prop-'.length)
            result[key] = parseValue(value)
        }
    })
    return result as Partial<TProps>
}

/**
 * Create reactive props.
 */
const createReactiveProps = <TProps extends Record<string, any>>(
    initial: Partial<TProps>,
    element: HTMLElement,
): TProps => {
    const proxy = {} as TProps

    Object.entries(initial).forEach(([key, val]) => {
        let internal = val
        Object.defineProperty(proxy, key, {
            get: () => internal,
            set: (newVal: any) => {
                if (internal === newVal) return
                internal = newVal

                // Update DOM attribute
                const attributeValue = typeof newVal === 'object' ? JSON.stringify(newVal) : String(newVal)
                element.setAttribute(`data-prop-${key}`, attributeValue)

                // Emit event
                element.dispatchEvent(
                    new CustomEvent('prop-changed', {
                        detail: {
                            key,
                            value: newVal,
                        },
                        bubbles: true,
                    }),
                )
            },
            enumerable: true,
            configurable: true,
        })
    })

    return proxy
}

/**
 * Component registry for managing instances.
 */
const componentRegistry = new WeakMap<HTMLElement, ComponentElement<any, any>>()

/**
 * Create component instance.
 */
const createComponentInstance = <TElement extends HTMLElement, TProps extends Record<string, any>>(
    element: TElement,
    props: TProps,
): ComponentElement<TElement, TProps> => {
    const eventListeners = new Map<string, Set<EventListener>>()
    const html = element.innerHTML

    // Create simple AST representation
    const childNodes = Array.from(element.childNodes)
    const ast: ComponentNode[] = []

    for (const node of childNodes) {
        if (node.nodeType === Node.ELEMENT_NODE) {
            const el = node as HTMLElement
            ast.push({
                type: 'element',
                tagName: el.tagName.toLowerCase(),
                attributes: Object.fromEntries(Array.from(el.attributes).map(attr => [attr.name, attr.value])),
            })
        } else if (node.nodeType === Node.TEXT_NODE && node.textContent?.trim()) {
            ast.push({
                type: 'text',
                textContent: node.textContent.trim(),
            })
        }
    }

    return {
        element,
        html,
        ast,
        props,

        updateProp<K extends keyof TProps>(key: K, value: TProps[K]): void {
            if (key in this.props) {
                ;(this.props as any)[key] = value
            }
        },

        getProp<K extends keyof TProps>(key: K): TProps[K] {
            return this.props[key]
        },

        hasProp(key: keyof TProps): boolean {
            return key in this.props
        },

        getPropKeys(): Array<keyof TProps> {
            return Object.keys(this.props) as Array<keyof TProps>
        },

        querySelector<T extends HTMLElement = HTMLElement>(selector: string): T | null {
            return this.element.querySelector<T>(selector)
        },

        querySelectorAll<T extends HTMLElement = HTMLElement>(selector: string): NodeListOf<T> {
            return this.element.querySelectorAll<T>(selector)
        },

        on(event: string, handler: EventListener): void {
            if (!eventListeners.has(event)) {
                eventListeners.set(event, new Set())
            }
            eventListeners.get(event)!.add(handler)
            this.element.addEventListener(event, handler)
        },

        off(event: string, handler: EventListener): void {
            const handlers = eventListeners.get(event)
            if (handlers) {
                handlers.delete(handler)
                if (handlers.size === 0) {
                    eventListeners.delete(event)
                }
            }
            this.element.removeEventListener(event, handler)
        },

        destroy(): void {
            eventListeners.forEach((handlers, event) => {
                handlers.forEach(handler => {
                    this.element.removeEventListener(event, handler)
                })
            })
            eventListeners.clear()
            componentRegistry.delete(this.element)
        },
    }
}

// ---------------------------------------------------------------------------
// Main Component Factory
// ---------------------------------------------------------------------------

/**
 * Create a reactive component bound to a DOM element.
 *
 * @example
 * ```typescript
 * // Basic usage
 * const component = createComponent<HTMLDivElement>('.my-div');
 *
 * // With callback
 * createComponent('.selector', (component) => {
 *   component.on('click', () => console.log('clicked'));
 * });
 *
 * // With typed props
 * interface MyProps {
 *   count: number;
 *   name: string;
 * }
 * const component = createComponent<HTMLFormElement, MyProps>('#my-form', {
 *   initialProps: { count: 0, name: 'test' }
 * });
 * ```
 */
export function createComponent<
    TElement extends HTMLElement = HTMLElement,
    TProps extends Record<string, any> = Record<string, any>,
>(
    selector: string | TElement,
    callbackOrOptions?: ((component: ComponentElement<TElement, TProps>) => void) | ComponentOptions<TProps>,
    options?: ComponentOptions<TProps>,
): ComponentElement<TElement, TProps> | null {
    // Determine if callback or options is provided
    let callback: ((component: ComponentElement<TElement, TProps>) => void) | undefined
    let finalOptions: ComponentOptions<TProps> = {}

    if (typeof callbackOrOptions === 'function') {
        callback = callbackOrOptions
        finalOptions = options || {}
    } else {
        finalOptions = callbackOrOptions || {}
    }

    // Resolve element
    const element = typeof selector === 'string' ? document.querySelector<TElement>(selector) : selector

    if (!element) {
        return null
    }

    // Extract props from data attributes
    const extractedProps = extractProps<TProps>(element)

    // Merge with initial props
    const mergedProps = {
        ...extractedProps,
        ...finalOptions.initialProps,
    } as TProps

    // Create reactive or static props
    const props = finalOptions.reactive === false ? mergedProps : createReactiveProps(mergedProps, element)

    // Create component
    const component = createComponentInstance<TElement, TProps>(element, props as TProps)

    // Register component
    componentRegistry.set(element, component)

    // Attach event handlers
    if (finalOptions.eventHandlers) {
        Object.entries(finalOptions.eventHandlers).forEach(([event, handler]) => {
            component.on(event, handler)
        })
    }

    // Execute callback if provided
    if (callback) {
        callback(component)
    }

    return component
}

// ---------------------------------------------------------------------------
// Utility Functions
// ---------------------------------------------------------------------------

/**
 * Get component instance from element.
 */
export const getComponentInstance = <
    TElement extends HTMLElement = HTMLElement,
    TProps extends Record<string, any> = Record<string, any>,
>(
    element: TElement,
): ComponentElement<TElement, TProps> | undefined => {
    return componentRegistry.get(element) as ComponentElement<TElement, TProps> | undefined
}

/**
 * Check if element has a component.
 */
export const hasComponent = (element: HTMLElement): boolean => {
    return componentRegistry.has(element)
}

/**
 * Destroy component and cleanup.
 */
export const destroyComponent = (element: HTMLElement): boolean => {
    const component = componentRegistry.get(element)
    if (component) {
        component.destroy()
        return true
    }
    return false
}

/**
 * Create multiple components from selector.
 */
export const createMultiple = <
    TElement extends HTMLElement = HTMLElement,
    TProps extends Record<string, any> = Record<string, any>,
>(
    selector: string,
    options?: ComponentOptions<TProps>,
): ComponentElement<TElement, TProps>[] => {
    const elements = document.querySelectorAll<TElement>(selector)
    return Array.from(elements)
        .map(el => createComponent<TElement, TProps>(el, options))
        .filter((component): component is ComponentElement<TElement, TProps> => component !== null)
}
