/**
 * Component instance interface that provides access to the DOM element and helper methods
 */
export interface ComponentInstance<T extends HTMLElement = HTMLElement> {
    /** The DOM element this component is bound to */
    element: T
    /** Find a child element by selector within this component */
    querySelector<E extends Element = Element>(selector: string): E | null
    /** Find all child elements by selector within this component */
    querySelectorAll<E extends Element = Element>(selector: string): NodeListOf<E>
    /** Props extracted from data-props-* attributes (reactive if enabled) */
    props: Record<string, any>
    /** useState hook for creating reactive state */
    useState: <T>(initialValue: T) => [T, (newValue: T | ((prev: T) => T)) => void]
    /** useContext hook for accessing shared context data */
    useContext: <T>(contextId: string) => ComponentContext<T> | null
}

/**
 * Component initialization callback function
 */
export type ComponentCallback<T extends HTMLElement = HTMLElement> = (
    component: ComponentInstance<T>,
) => void | (() => void)

/**
 * Component configuration object
 */
export interface ComponentConfig<T extends HTMLElement = HTMLElement> {
    id: string
    selector: string
    callback: ComponentCallback<T>
    reactive?: boolean
    context?: string[] // Context IDs this component should have access to
}

/**
 * Context interface for sharing data between components
 */
export interface ComponentContext<T = any> {
    /** Context ID */
    id: string
    /** Get current context value */
    getValue: () => T
    /** Set context value and notify all subscribers */
    setValue: (newValue: T | ((prev: T) => T)) => void
    /** Subscribe to context changes */
    subscribe: (callback: (value: T) => void) => () => void
    /** Emit custom events through the context */
    emit: (eventType: string, data?: any) => void
    /** Listen for custom events from the context */
    on: (eventType: string, callback: (data: any) => void) => () => void
}

/**
 * Context manager for handling shared state between components
 */
class ComponentContextManager {
    private contexts = new Map<
        string,
        {
            value: any
            subscribers: Set<(value: any) => void>
            eventListeners: Map<string, Set<(data: any) => void>>
        }
    >()

    createContext<T>(id: string, initialValue: T): ComponentContext<T> {
        if (!this.contexts.has(id)) {
            this.contexts.set(id, {
                value: initialValue,
                subscribers: new Set(),
                eventListeners: new Map(),
            })
        }

        const context = this.contexts.get(id)!

        return {
            id,
            getValue: () => context.value,
            setValue: (newValue: T | ((prev: T) => T)) => {
                const currentValue = context.value
                const nextValue = typeof newValue === 'function' ? (newValue as (prev: T) => T)(currentValue) : newValue

                if (currentValue !== nextValue) {
                    context.value = nextValue
                    context.subscribers.forEach(callback => callback(nextValue))
                }
            },
            subscribe: (callback: (value: T) => void) => {
                context.subscribers.add(callback)
                return () => context.subscribers.delete(callback)
            },
            emit: (eventType: string, data?: any) => {
                const listeners = context.eventListeners.get(eventType)
                if (listeners) {
                    listeners.forEach(callback => callback(data))
                }
            },
            on: (eventType: string, callback: (data: any) => void) => {
                if (!context.eventListeners.has(eventType)) {
                    context.eventListeners.set(eventType, new Set())
                }
                context.eventListeners.get(eventType)!.add(callback)

                return () => {
                    const listeners = context.eventListeners.get(eventType)
                    if (listeners) {
                        listeners.delete(callback)
                        if (listeners.size === 0) {
                            context.eventListeners.delete(eventType)
                        }
                    }
                }
            },
        }
    }

    getContext<T>(id: string): ComponentContext<T> | null {
        if (!this.contexts.has(id)) {
            return null
        }

        return this.createContext(id, this.contexts.get(id)!.value)
    }

    deleteContext(id: string): void {
        this.contexts.delete(id)
    }

    cleanup(): void {
        this.contexts.clear()
    }
}

const globalContextManager = new ComponentContextManager()

/**
 * State management for reactive components
 */
class ComponentState {
    private state = new Map<string, any>()
    private watchers = new Map<string, Set<() => void>>()
    private stateCounter = 0

    createState<T>(initialValue: T, componentId: string): [T, (newValue: T | ((prev: T) => T)) => void] {
        const stateKey = `${componentId}-state-${this.stateCounter++}`
        this.state.set(stateKey, initialValue)
        this.watchers.set(stateKey, new Set())

        const getValue = (): T => this.state.get(stateKey)

        const setValue = (newValue: T | ((prev: T) => T)): void => {
            const currentValue = this.state.get(stateKey)
            const nextValue = typeof newValue === 'function' ? (newValue as (prev: T) => T)(currentValue) : newValue

            if (currentValue !== nextValue) {
                this.state.set(stateKey, nextValue)
                this.notifyWatchers(stateKey)
            }
        }

        return [getValue(), setValue]
    }

    private notifyWatchers(stateKey: string): void {
        const watchers = this.watchers.get(stateKey)
        if (watchers) {
            watchers.forEach(watcher => watcher())
        }
    }

    addWatcher(stateKey: string, watcher: () => void): void {
        const watchers = this.watchers.get(stateKey) || new Set()
        watchers.add(watcher)
        this.watchers.set(stateKey, watchers)
    }

    cleanup(componentId: string): void {
        // Remove all state and watchers for this component
        const keysToRemove = Array.from(this.state.keys()).filter(key => key.startsWith(`${componentId}-state-`))
        keysToRemove.forEach(key => {
            this.state.delete(key)
            this.watchers.delete(key)
        })
    }
}

const globalComponentState = new ComponentState()

/**
 * Extract props from data-props-* attributes on an element
 */
function extractProps(element: HTMLElement, reactive = false): Record<string, any> {
    const props: Record<string, any> = {}

    // Get all attributes that start with "data-props-"
    Array.from(element.attributes).forEach(attr => {
        if (attr.name.startsWith('data-props-')) {
            const propName = attr.name
                .replace('data-props-', '')
                .replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())

            // Try to parse as JSON first, fallback to string
            try {
                props[propName] = JSON.parse(attr.value)
            } catch {
                props[propName] = attr.value
            }
        }
    })

    if (reactive) {
        return createReactiveProps(props, element)
    }

    return props
}

/**
 * Create reactive props that update when attributes change
 */
function createReactiveProps(initialProps: Record<string, any>, element: HTMLElement): Record<string, any> {
    const reactiveProps = { ...initialProps }

    // Create a MutationObserver to watch for attribute changes
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.type === 'attributes' && mutation.attributeName?.startsWith('data-props-')) {
                const propName = mutation.attributeName
                    .replace('data-props-', '')
                    .replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())

                const newValue = element.getAttribute(mutation.attributeName)

                if (newValue !== null) {
                    try {
                        reactiveProps[propName] = JSON.parse(newValue)
                    } catch {
                        reactiveProps[propName] = newValue
                    }
                } else {
                    delete reactiveProps[propName]
                }

                // Trigger custom event for prop changes
                element.dispatchEvent(
                    new CustomEvent('props:changed', {
                        detail: { propName, newValue: reactiveProps[propName] },
                    }),
                )
            }
        })
    })

    observer.observe(element, {
        attributes: true,
        attributeFilter: Array.from(element.attributes)
            .map(attr => attr.name)
            .filter(name => name.startsWith('data-props-')),
    })

    // Store observer for cleanup
    ;(element as any).__propsObserver = observer

    return reactiveProps
}

/**
 * Create a component instance with extracted props and helper methods
 */
function createComponentInstance<T extends HTMLElement>(
    element: T,
    reactive = false,
    componentId: string,
    availableContexts: string[] = [],
): ComponentInstance<T> {
    const props = extractProps(element, reactive)

    const useState = <StateType>(initialValue: StateType) => {
        return globalComponentState.createState(initialValue, componentId)
    }

    const useContext = <ContextType>(contextId: string): ComponentContext<ContextType> | null => {
        if (!availableContexts.includes(contextId)) {
            console.warn(
                `Component ${componentId} does not have access to context ${contextId}. Add it to the context array in component configuration.`,
            )
            return null
        }
        return globalContextManager.getContext<ContextType>(contextId)
    }

    return {
        element,
        querySelector: (selector: string) => element.querySelector(selector),
        querySelectorAll: (selector: string) => element.querySelectorAll(selector),
        props,
        useState,
        useContext,
    }
}

/**
 * Create a component configuration that can be mounted later
 *
 * @param id - Unique identifier for this component
 * @param selector - CSS selector to find component elements
 * @param callback - Function to initialize each component instance
 * @param options - Configuration options
 * @returns Component configuration object
 *
 * @example
 * ```typescript
 * const component = createComponent('newsletter-dialog', '[data-component="newsletter-dialog"]', component => {
 *     const { element, useState, useContext } = component
 *     const userContext = useContext<{ name: string }>('user')
 *     const [isOpen, setIsOpen] = useState(false)
 *
 *     setIsOpen(true) // Opens the dialog
 * }, { reactive: true, context: ['user', 'theme'] })
 *
 * mount(component)
 * ```
 */
export function createComponent<T extends HTMLElement = HTMLElement>(
    id: string,
    selector: string,
    callback: ComponentCallback<T>,
    options: { reactive?: boolean; context?: string[] } = {},
): ComponentConfig<T> {
    return {
        id,
        selector,
        callback,
        reactive: options.reactive || false,
        context: options.context || [],
    }
}

/**
 * Initialize components immediately without mounting lifecycle
 *
 * @param selector - CSS selector to find component elements
 * @param callback - Function to initialize each component instance
 * @param reactive - Whether to make props reactive
 * @param componentId - Component identifier
 * @param availableContexts - Contexts this component can access
 * @returns Array of cleanup functions (if provided by callbacks)
 */
export function initializeComponents<T extends HTMLElement = HTMLElement>(
    selector: string,
    callback: ComponentCallback<T>,
    reactive = false,
    componentId = 'anonymous',
    availableContexts: string[] = [],
): Array<(() => void) | void> {
    const elements = document.querySelectorAll<T>(selector)
    const cleanupFunctions: Array<(() => void) | void> = []

    elements.forEach((element, index) => {
        const instanceId = `${componentId}-${index}`
        const componentInstance = createComponentInstance(element, reactive, instanceId, availableContexts)
        const cleanup = callback(componentInstance)

        // Wrap cleanup to include state cleanup
        const enhancedCleanup = () => {
            // Run user cleanup first
            if (typeof cleanup === 'function') {
                cleanup()
            }

            // Clean up reactive props observer
            if (reactive && (element as any).__propsObserver) {
                ;(element as any).__propsObserver.disconnect()
                delete (element as any).__propsObserver
            }

            // Clean up component state
            globalComponentState.cleanup(instanceId)
        }

        cleanupFunctions.push(enhancedCleanup)
    })

    return cleanupFunctions
}

/**
 * Create a single component instance for the first matching element
 *
 * @param selector - CSS selector to find the component element
 * @param callback - Function to initialize the component instance
 * @returns Cleanup function (if provided by callback)
 */
export function createSingleComponent<T extends HTMLElement = HTMLElement>(
    selector: string,
    callback: ComponentCallback<T>,
): (() => void) | void {
    const element = document.querySelector<T>(selector)

    if (!element) {
        console.warn(`No element found for selector: ${selector}`)
        return
    }

    const componentInstance = createComponentInstance(element)
    return callback(componentInstance)
}

/**
 * Mount a component configuration with automatic DOM ready and Turbo support
 * This function handles DOM ready, Turbo ready, and Turbo page changes
 *
 * @param component - Component configuration object
 * @returns Promise that resolves to array of cleanup functions
 *
 * @example
 * ```typescript
 * const component = createComponent('newsletter-dialog', '[data-component="newsletter-dialog"]', component => {
 *     const { element, useState, useContext } = component
 *     const userContext = useContext<{ name: string }>('user')
 *     const [count, setCount] = useState(0)
 *
 *     setCount(count + 1)
 * }, { reactive: true, context: ['user'] })
 *
 * mount(component)
 * ```
 */
export function mount<T extends HTMLElement = HTMLElement>(
    component: ComponentConfig<T>,
): Promise<Array<(() => void) | void>> {
    const { id, selector, callback, reactive, context } = component

    if (!id) {
        throw new Error('mount: component "id" must be a non-empty string')
    }

    const attrName = `data-mounted-${id}`
    let cleanupFunctions: Array<(() => void) | void> = []

    return new Promise(resolve => {
        const runOnce = (): void => {
            const documentBody = document.body

            if (documentBody.hasAttribute(attrName)) {
                resolve(cleanupFunctions)
                return
            }

            try {
                // Mark as mounted before running
                documentBody.setAttribute(attrName, 'true')
                cleanupFunctions = initializeComponents(selector, callback, reactive, id, context)
                resolve(cleanupFunctions)
            } catch (err) {
                // If initialization throws, clean up the flag so you can retry
                documentBody.removeAttribute(attrName)
                throw err
            }
        }

        const unmountOnTurboBeforeRender = (): void => {
            const documentBody = document.body
            if (documentBody.hasAttribute(attrName)) {
                // Run cleanup functions before page change
                cleanupFunctions.forEach(cleanup => {
                    if (typeof cleanup === 'function') {
                        try {
                            cleanup()
                        } catch (err) {
                            console.warn('Error during component cleanup:', err)
                        }
                    }
                })
                documentBody.removeAttribute(attrName)
                cleanupFunctions = []
            }
        }

        // Check if Turbo is available
        const isTurbo =
            typeof window !== 'undefined' &&
            typeof (window as any).Turbo === 'object' &&
            typeof (window as any).Turbo.navigator === 'object'

        if (isTurbo) {
            // Handle Turbo navigation lifecycle
            document.addEventListener('turbo:before-render', unmountOnTurboBeforeRender)
            document.addEventListener('turbo:load', runOnce)

            // If page is already loaded, run immediately
            if (document.readyState !== 'loading') {
                runOnce()
            }
        } else {
            // Fallback to standard DOM events
            const loadEvent = 'DOMContentLoaded'

            if (document.readyState === 'loading') {
                document.addEventListener(loadEvent, runOnce, { once: true })
            } else {
                runOnce()
            }
        }
    })
}

/**
 * Create and mount a component in one step
 *
 * @param id - Unique identifier for this component
 * @param selector - CSS selector to find component elements
 * @param callback - Function to initialize each component instance
 * @param options - Configuration options
 * @returns Promise that resolves to array of cleanup functions
 *
 * @example
 * ```typescript
 * createComponentAndMount('newsletter-dialog', '[data-component="newsletter-dialog"]', component => {
 *     const { element, useState, useContext } = component
 *     const themeContext = useContext<{ dark: boolean }>('theme')
 *     const [isVisible, setIsVisible] = useState(false)
 *
 *     element.addEventListener('click', () => setIsVisible(!isVisible))
 * }, { reactive: true, context: ['theme'] })
 * ```
 */
export function createComponentAndMount<T extends HTMLElement = HTMLElement>(
    id: string,
    selector: string,
    callback: ComponentCallback<T>,
    options: { reactive?: boolean; context?: string[] } = {},
): Promise<Array<(() => void) | void>> {
    const component = createComponent(id, selector, callback, options)
    return mount(component)
}

/**
 * Create a shared context that components can use to communicate
 *
 * @param id - Unique context identifier
 * @param initialValue - Initial value for the context
 * @returns ComponentContext instance
 *
 * @example
 * ```typescript
 * // Create contexts
 * const userContext = createContext('user', { name: 'John', isLoggedIn: false })
 * const themeContext = createContext('theme', { dark: false, color: 'blue' })
 *
 * // Use in components
 * const component = createComponent('header', '[data-component="header"]', component => {
 *     const { useContext } = component
 *     const user = useContext<{ name: string; isLoggedIn: boolean }>('user')
 *     const theme = useContext<{ dark: boolean; color: string }>('theme')
 *
 *     if (user && theme) {
 *         // Subscribe to changes
 *         user.subscribe(userData => {
 *             console.log('User changed:', userData)
 *         })
 *
 *         // Update context
 *         user.setValue({ name: 'Jane', isLoggedIn: true })
 *
 *         // Listen for custom events
 *         user.on('login', (data) => {
 *             console.log('User logged in:', data)
 *         })
 *
 *         // Emit custom events
 *         theme.emit('colorChanged', { newColor: 'red' })
 *     }
 * }, { context: ['user', 'theme'] })
 * ```
 */
export function createContext<T>(id: string, initialValue: T): ComponentContext<T> {
    return globalContextManager.createContext(id, initialValue)
}

/**
 * Get an existing context by ID
 *
 * @param id - Context identifier
 * @returns ComponentContext instance or null if not found
 */
export function getContext<T>(id: string): ComponentContext<T> | null {
    return globalContextManager.getContext<T>(id)
}

/**
 * Delete a context and clean up all its subscribers
 *
 * @param id - Context identifier to delete
 */
export function deleteContext(id: string): void {
    globalContextManager.deleteContext(id)
}
