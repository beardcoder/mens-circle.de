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

// ES2025: Use WeakMap for element-specific data to prevent memory leaks
const elementPropsCache = new WeakMap<HTMLElement, Record<string, any>>()
const elementObserverCache = new WeakMap<HTMLElement, MutationObserver>()

/**
 * Context manager for handling shared state between components - ES2025 Optimized
 */
class ComponentContextManager {
    // ES2025: Private fields with # syntax
    #contexts = new Map<
        string,
        {
            value: any
            subscribers: Set<(value: any) => void>
            eventListeners: Map<string, Set<(data: any) => void>>
        }
    >()

    // ES2025: Private field for context cache
    #contextCache = new Map<string, ComponentContext<any>>()

    // ES2025: Private field for notification queue
    #notificationQueue = new Set<() => void>()
    #notificationScheduled = false

    createContext<T>(id: string, initialValue: T): ComponentContext<T> {
        // Return cached instance if it exists
        if (this.#contextCache.has(id)) {
            return this.#contextCache.get(id)!
        }

        if (!this.#contexts.has(id)) {
            this.#contexts.set(id, {
                value: initialValue,
                subscribers: new Set(),
                eventListeners: new Map(),
            })
        }

        const context = this.#contexts.get(id)!

        const contextInstance: ComponentContext<T> = {
            id,
            getValue: () => context.value,
            setValue: (newValue: T | ((prev: T) => T)) => {
                const currentValue = context.value
                const nextValue = typeof newValue === 'function' ? (newValue as (prev: T) => T)(currentValue) : newValue

                // ES2025: Object.is for better comparison including NaN and -0
                if (!Object.is(currentValue, nextValue)) {
                    context.value = nextValue
                    // ES2025: Batch notifications using requestAnimationFrame
                    this.#scheduleNotification(() => {
                        context.subscribers.forEach(callback => callback(nextValue))
                    })
                }
            },
            subscribe: (callback: (value: T) => void) => {
                context.subscribers.add(callback)
                return () => context.subscribers.delete(callback)
            },
            emit: (eventType: string, data?: any) => {
                const listeners = context.eventListeners.get(eventType)
                if (listeners?.size) {
                    // ES2025: Batch event emissions
                    this.#scheduleNotification(() => {
                        listeners.forEach(callback => callback(data))
                    })
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

        // Cache the context instance
        this.#contextCache.set(id, contextInstance)
        return contextInstance
    }

    // ES2025: Private method for scheduling notifications
    #scheduleNotification(callback: () => void): void {
        this.#notificationQueue.add(callback)

        if (!this.#notificationScheduled) {
            this.#notificationScheduled = true
            requestAnimationFrame(() => {
                this.#notificationQueue.forEach(cb => {
                    try {
                        cb()
                    } catch (error) {
                        console.error('Error in context notification:', error)
                    }
                })
                this.#notificationQueue.clear()
                this.#notificationScheduled = false
            })
        }
    }

    getContext<T>(id: string): ComponentContext<T> | null {
        if (!this.#contexts.has(id)) {
            return null
        }

        // Return cached instance if available
        if (this.#contextCache.has(id)) {
            return this.#contextCache.get(id)! as ComponentContext<T>
        }

        return this.createContext(id, this.#contexts.get(id)!.value)
    }

    deleteContext(id: string): void {
        this.#contexts.delete(id)
        this.#contextCache.delete(id)
    }

    cleanup(): void {
        this.#contexts.clear()
        this.#contextCache.clear()
        this.#notificationQueue.clear()
        this.#notificationScheduled = false
    }
}

const globalContextManager = new ComponentContextManager()

/**
 * State management for reactive components - ES2025 Optimized
 */
class ComponentState {
    // ES2025: Private fields
    #state = new Map<string, any>()
    #watchers = new Map<string, Set<() => void>>()
    #stateCounter = 0

    createState<T>(initialValue: T, componentId: string): [T, (newValue: T | ((prev: T) => T)) => void] {
        const stateKey = `${componentId}-state-${this.#stateCounter++}`
        this.#state.set(stateKey, initialValue)
        this.#watchers.set(stateKey, new Set())

        // ES2025: Arrow function with cached getter
        const getValue = (): T => this.#state.get(stateKey)

        const setValue = (newValue: T | ((prev: T) => T)): void => {
            const currentValue = this.#state.get(stateKey)
            const nextValue = typeof newValue === 'function' ? (newValue as (prev: T) => T)(currentValue) : newValue

            // ES2025: Object.is for better comparison
            if (!Object.is(currentValue, nextValue)) {
                this.#state.set(stateKey, nextValue)
                this.#notifyWatchers(stateKey)
            }
        }

        return [getValue(), setValue]
    }

    // ES2025: Private method
    #notifyWatchers(stateKey: string): void {
        const watchers = this.#watchers.get(stateKey)
        if (watchers?.size) {
            // ES2025: Use requestAnimationFrame for batched DOM updates
            requestAnimationFrame(() => {
                watchers.forEach(watcher => {
                    try {
                        watcher()
                    } catch (error) {
                        console.error('Error in state watcher:', error)
                    }
                })
            })
        }
    }

    cleanup(componentId: string): void {
        // ES2025: Use for...of with destructuring for better performance
        for (const [key] of this.#state) {
            if (key.startsWith(`${componentId}-state-`)) {
                this.#state.delete(key)
                this.#watchers.delete(key)
            }
        }
    }
}

const globalComponentState = new ComponentState()

// ES2025: Pre-compile regex and use static constants
const KEBAB_CASE_REGEX = /-([a-z])/g
const DATA_PROPS_PREFIX = 'data-props-'

/**
 * Extract props from data-props-* attributes - ES2025 Optimized
 */
function extractProps(element: HTMLElement, reactive = false): Record<string, any> {
    // Check cache first
    if (!reactive && elementPropsCache.has(element)) {
        return elementPropsCache.get(element)!
    }

    const props: Record<string, any> = {}
    const { attributes } = element

    // ES2025: Use for...of loop for better performance and readability
    for (const attr of attributes) {
        if (attr.name.startsWith(DATA_PROPS_PREFIX)) {
            const propName = attr.name
                .slice(DATA_PROPS_PREFIX.length)
                .replace(KEBAB_CASE_REGEX, (_, letter) => letter.toUpperCase())

            // ES2025: Use pattern matching-like approach for value parsing
            let value: any = attr.value

            // ES2025: More efficient value parsing with early checks
            if (value === 'true' || value === 'false') {
                value = value === 'true'
            } else if (!Number.isNaN(Number(value)) && value !== '') {
                value = Number(value)
            } else if (value.startsWith('{') || value.startsWith('[')) {
                try {
                    value = JSON.parse(value)
                } catch {
                    // Keep as string if JSON parsing fails
                }
            }

            props[propName] = value
        }
    }

    // Cache non-reactive props
    if (!reactive) {
        elementPropsCache.set(element, props)
    }

    return reactive ? createReactiveProps(props, element) : props
}

/**
 * Create reactive props - ES2025 Optimized
 */
function createReactiveProps(initialProps: Record<string, any>, element: HTMLElement): Record<string, any> {
    // Check if observer already exists
    if (elementObserverCache.has(element)) {
        return { ...initialProps }
    }

    const reactiveProps = { ...initialProps }

    // ES2025: Use Array.from with filter chaining for better readability
    const dataPropsAttributes = Array.from(element.attributes)
        .map(attr => attr.name)
        .filter(name => name.startsWith(DATA_PROPS_PREFIX))

    if (dataPropsAttributes.length === 0) {
        return reactiveProps
    }

    // ES2025: Use let flag for throttling
    let updateScheduled = false

    const observer = new MutationObserver(mutations => {
        if (updateScheduled) return
        updateScheduled = true

        requestAnimationFrame(() => {
            let hasChanges = false

            // ES2025: Use for...of for better performance
            for (const mutation of mutations) {
                if (mutation.type === 'attributes' && mutation.attributeName?.startsWith(DATA_PROPS_PREFIX)) {
                    const propName = mutation.attributeName
                        .slice(DATA_PROPS_PREFIX.length)
                        .replace(KEBAB_CASE_REGEX, (_, letter) => letter.toUpperCase())

                    const newValue = element.getAttribute(mutation.attributeName)

                    if (newValue !== null) {
                        // ES2025: Improved value parsing
                        let parsedValue: any = newValue
                        if (newValue === 'true' || newValue === 'false') {
                            parsedValue = newValue === 'true'
                        } else if (!Number.isNaN(Number(newValue)) && newValue !== '') {
                            parsedValue = Number(newValue)
                        } else if (newValue.startsWith('{') || newValue.startsWith('[')) {
                            try {
                                parsedValue = JSON.parse(newValue)
                            } catch {
                                // Keep as string
                            }
                        }
                        reactiveProps[propName] = parsedValue
                    } else {
                        // ES2025: Use delete operator
                        delete reactiveProps[propName]
                    }
                    hasChanges = true
                }
            }

            if (hasChanges) {
                // Only dispatch event if there were actual changes
                element.dispatchEvent(
                    new CustomEvent('props:changed', {
                        detail: { props: { ...reactiveProps } },
                    }),
                )
            }

            updateScheduled = false
        })
    })

    observer.observe(element, {
        attributes: true,
        attributeFilter: dataPropsAttributes,
    })

    // Cache observer for cleanup
    elementObserverCache.set(element, observer)
    return reactiveProps
}

// ES2025: Cache component instances using WeakMap
const componentInstanceCache = new WeakMap<HTMLElement, ComponentInstance<any>>()

/**
 * Create component instance - ES2025 Optimized
 */
function createComponentInstance<T extends HTMLElement>(
    element: T,
    reactive = false,
    componentId: string,
    availableContexts: string[] = [],
): ComponentInstance<T> {
    // Return cached instance if available (for non-reactive components)
    if (!reactive && componentInstanceCache.has(element)) {
        return componentInstanceCache.get(element)!
    }

    const props = extractProps(element, reactive)

    // ES2025: Create bound methods once using bind
    const querySelector = element.querySelector.bind(element)
    const querySelectorAll = element.querySelectorAll.bind(element)

    // ES2025: Arrow functions for hooks
    const useState = <StateType>(initialValue: StateType) => {
        return globalComponentState.createState(initialValue, componentId)
    }

    // ES2025: Use Set for O(1) lookup performance
    const contextAccessCache = new Set(availableContexts)

    const useContext = <ContextType>(contextId: string): ComponentContext<ContextType> | null => {
        if (!contextAccessCache.has(contextId)) {
            console.warn(
                `Component ${componentId} does not have access to context ${contextId}. Add it to the context array in component configuration.`,
            )
            return null
        }
        return globalContextManager.getContext<ContextType>(contextId)
    }

    const instance: ComponentInstance<T> = {
        element,
        querySelector,
        querySelectorAll,
        props,
        useState,
        useContext,
    }

    // Cache instance for non-reactive components
    if (!reactive) {
        componentInstanceCache.set(element, instance)
    }

    return instance
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
 * Initialize components immediately without mounting lifecycle - Optimized version
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

    // Performance: Use for loop instead of forEach for better performance
    for (let i = 0; i < elements.length; i++) {
        const element = elements[i]
        const instanceId = `${componentId}-${i}`

        try {
            const componentInstance = createComponentInstance(element, reactive, instanceId, availableContexts)
            const cleanup = callback(componentInstance)

            // Wrap cleanup to include state cleanup
            const enhancedCleanup = () => {
                // Run user cleanup first
                if (typeof cleanup === 'function') {
                    try {
                        cleanup()
                    } catch (error) {
                        console.error(`Error in cleanup for component ${instanceId}:`, error)
                    }
                }

                // Clean up reactive props observer
                if (reactive) {
                    const observer = elementObserverCache.get(element)
                    if (observer) {
                        observer.disconnect()
                        elementObserverCache.delete(element)
                    }
                }

                // Clean up component state
                globalComponentState.cleanup(instanceId)

                // Clean up caches
                elementPropsCache.delete(element)
                componentInstanceCache.delete(element)
            }

            cleanupFunctions.push(enhancedCleanup)
        } catch (error) {
            console.error(`Error initializing component ${instanceId}:`, error)
        }
    }

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
 * Performance monitoring utilities
 */
export const PerformanceMonitor = {
    /**
     * Get performance metrics for the component system
     */
    getMetrics(): {
        cachedProps: number
        cachedObservers: number
        cachedInstances: number
        activeContexts: number
        activeStates: number
    } {
        return {
            cachedProps: elementPropsCache instanceof WeakMap ? -1 : 0, // WeakMap size not accessible
            cachedObservers: elementObserverCache instanceof WeakMap ? -1 : 0,
            cachedInstances: componentInstanceCache instanceof WeakMap ? -1 : 0,
            activeContexts: globalContextManager['contexts'].size,
            activeStates: globalComponentState['state'].size,
        }
    },

    /**
     * Clear all caches (use with caution)
     */
    clearCaches(): void {
        // Note: Can't clear WeakMaps directly, they'll be garbage collected automatically
        globalContextManager.cleanup()
        turboDetectionCache = null
        console.log('Component caches cleared')
    },

    /**
     * Measure component initialization time
     */
    measureComponentInit<T extends HTMLElement>(
        componentId: string,
        selector: string,
        callback: ComponentCallback<T>,
    ): Promise<{ duration: number; count: number }> {
        return new Promise(resolve => {
            const startTime = performance.now()
            const elements = document.querySelectorAll<T>(selector)

            elements.forEach((element, index) => {
                const instanceId = `${componentId}-${index}`
                const componentInstance = createComponentInstance(element, false, instanceId, [])
                callback(componentInstance)
            })

            const endTime = performance.now()
            resolve({
                duration: endTime - startTime,
                count: elements.length,
            })
        })
    },
}

// Performance: Cache Turbo detection result
let turboDetectionCache: boolean | null = null

/**
 * Detect if Turbo is available - Cached for performance
 */
function isTurboAvailable(): boolean {
    if (turboDetectionCache !== null) {
        return turboDetectionCache
    }

    turboDetectionCache =
        typeof window !== 'undefined' &&
        typeof (window as any).Turbo === 'object' &&
        typeof (window as any).Turbo.navigator === 'object'

    return turboDetectionCache
}

/**
 * Mount a component configuration with automatic DOM ready and Turbo support - Optimized version
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
                console.error(`Error mounting component ${id}:`, err)
                throw err
            }
        }

        const unmountOnTurboBeforeRender = (): void => {
            const documentBody = document.body
            if (documentBody.hasAttribute(attrName)) {
                // Performance: Use requestAnimationFrame for cleanup to avoid blocking
                requestAnimationFrame(() => {
                    cleanupFunctions.forEach(cleanup => {
                        if (typeof cleanup === 'function') {
                            try {
                                cleanup()
                            } catch (err) {
                                console.warn(`Error during component cleanup for ${id}:`, err)
                            }
                        }
                    })
                })
                documentBody.removeAttribute(attrName)
                cleanupFunctions = []
            }
        }

        // Performance: Use cached Turbo detection
        const isTurbo = isTurboAvailable()

        if (isTurbo) {
            // Handle Turbo navigation lifecycle
            document.addEventListener('turbo:before-render', unmountOnTurboBeforeRender, { passive: true })
            document.addEventListener('turbo:load', runOnce, { passive: true })

            // If page is already loaded, run immediately
            if (document.readyState !== 'loading') {
                // Performance: Use setTimeout to avoid blocking main thread
                setTimeout(runOnce, 0)
            }
        } else {
            // Fallback to standard DOM events
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', runOnce, { once: true, passive: true })
            } else {
                // Performance: Use setTimeout to avoid blocking main thread
                setTimeout(runOnce, 0)
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
