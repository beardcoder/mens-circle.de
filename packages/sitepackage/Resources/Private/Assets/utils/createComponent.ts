/**
 * Simple Component System
 *
 * A lightweight component system for creating reactive UI components with:
 * - State management
 * - Reactive props
 * - Cleanup handling
 * - Turbo support
 */

// ============================================================================
// TYPE DEFINITIONS
// ============================================================================

/** State setter function type */
export type StateSetter<T> = (newValue: T | ((prev: T) => T)) => void

/** State hook return type */
export type StateHook<T> = [T, StateSetter<T>]

/** Component cleanup function */
export type CleanupFunction = () => void

/** Reference hook return type */
export type RefHook<T extends Element> = T | null

/**
 * Component instance interface that provides access to the DOM element and helper methods
 */
export interface ComponentInstance<T extends HTMLElement = HTMLElement> {
    /** The DOM element this component is bound to */
    readonly element: T
    /** Find a child element by selector within this component */
    querySelector<E extends Element = Element>(selector: string): E | null
    /** Find all child elements by selector within this component */
    querySelectorAll<E extends Element = Element>(selector: string): NodeListOf<E>
    /** Props extracted from data-props-* attributes (reactive if enabled) */
    readonly props: Record<string, unknown>
    /** useState hook for creating reactive state */
    useState: <TState>(initialValue: TState) => StateHook<TState>
    /** useRef hook for referencing DOM elements by ref attribute */
    useRef: <TElement extends Element = Element>(refName: string) => RefHook<TElement>
}

/**
 * Component initialization callback function
 */
export type ComponentCallback<T extends HTMLElement = HTMLElement> = (
    component: ComponentInstance<T>,
) => CleanupFunction | undefined

/**
 * Component configuration object
 */
export interface ComponentConfig<T extends HTMLElement = HTMLElement> {
    readonly id: string
    readonly selector: string
    readonly callback: ComponentCallback<T>
    readonly reactive?: boolean
}

/**
 * Component mount options
 */
export interface ComponentMountOptions {
    readonly reactive?: boolean
}

// ============================================================================
// CONSTANTS
// ============================================================================

const KEBAB_CASE_REGEX = /-([a-z])/g
const DATA_PROPS_PREFIX = 'data-props-'
const MOUNTED_ATTR_PREFIX = 'data-mounted-'

// ============================================================================
// WEAK MAPS FOR MEMORY EFFICIENCY
// ============================================================================

const elementObserverCache = new WeakMap<HTMLElement, MutationObserver>()
const componentInstanceCache = new WeakMap<HTMLElement, ComponentInstance<HTMLElement>>()

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Converts kebab-case to camelCase
 */
function kebabToCamelCase(str: string): string {
    return str.replace(KEBAB_CASE_REGEX, (_, letter: string) => letter.toUpperCase())
}

/**
 * Safely parses a string value to its appropriate type
 */
function parseValue(value: string): unknown {
    // Handle boolean values
    if (value === 'true') return true
    if (value === 'false') return false

    // Handle empty string
    if (value === '') return ''

    // Handle numeric values
    const numValue = Number(value)
    if (!Number.isNaN(numValue)) return numValue

    // Handle JSON values
    if (value.startsWith('{') || value.startsWith('[')) {
        try {
            return JSON.parse(value)
        } catch {
            // Keep as string if JSON parsing fails
        }
    }

    return value
}

/**
 * Check if Turbo is available
 */
function isTurboAvailable(): boolean {
    return (
        typeof window !== 'undefined' &&
        'Turbo' in window &&
        typeof (window as { Turbo?: { navigator?: object } }).Turbo === 'object'
    )
}

// ============================================================================
// STATE MANAGEMENT
// ============================================================================

const componentStates = new WeakMap<HTMLElement, Map<string, unknown>>()

function createStateHook<T>(element: HTMLElement, key: string, initialValue: T): StateHook<T> {
    if (!componentStates.has(element)) {
        componentStates.set(element, new Map())
    }

    const states = componentStates.get(element)
    if (states && !states.has(key)) {
        states.set(key, initialValue)
    }

    const getValue = (): T => (states?.get(key) as T) ?? initialValue

    const setValue: StateSetter<T> = newValue => {
        if (!states) return

        const currentValue = (states.get(key) as T) ?? initialValue
        const nextValue = typeof newValue === 'function' ? (newValue as (prev: T) => T)(currentValue) : newValue

        if (!Object.is(currentValue, nextValue)) {
            states.set(key, nextValue)
        }
    }

    return [getValue(), setValue]
}

// ============================================================================
// PROPS MANAGEMENT
// ============================================================================

/**
 * Extract props from data-props-* attributes
 * Using direct getAttribute() for better performance than dataset
 */
function extractProps(element: HTMLElement, reactive = false): Record<string, unknown> {
    const props: Record<string, unknown> = {}
    const attributes = element.attributes
    const prefixLength = DATA_PROPS_PREFIX.length

    // Direct attribute iteration is faster than dataset
    for (const attr of attributes) {
        if (attr.name.startsWith(DATA_PROPS_PREFIX)) {
            const propName = kebabToCamelCase(attr.name.slice(prefixLength))
            props[propName] = parseValue(attr.value)
        }
    }

    if (reactive) {
        setupReactiveProps(element, props)
    }

    return props
}

/**
 * Setup reactive props with mutation observer
 * Optimized for performance with direct attribute access
 */
function setupReactiveProps(element: HTMLElement, props: Record<string, unknown>): void {
    if (elementObserverCache.has(element)) return

    const prefixLength = DATA_PROPS_PREFIX.length

    const observer = new MutationObserver(() => {
        let hasChanges = false
        const attributes = element.attributes

        // Use traditional for loop for better performance
        for (const attr of attributes) {
            if (attr.name.startsWith(DATA_PROPS_PREFIX)) {
                const propName = kebabToCamelCase(attr.name.slice(prefixLength))
                const newValue = parseValue(attr.value)

                if (props[propName] !== newValue) {
                    props[propName] = newValue
                    hasChanges = true
                }
            }
        }

        if (hasChanges) {
            element.dispatchEvent(new CustomEvent('props:changed', { detail: { props } }))
        }
    })

    observer.observe(element, { attributes: true })
    elementObserverCache.set(element, observer)
}

// ============================================================================
// REF MANAGEMENT
// ============================================================================

/**
 * Create a ref hook that finds elements by ref attribute within the component
 */
function createRefHook<T extends HTMLElement>(
    element: T,
): <TElement extends Element = Element>(refName: string) => RefHook<TElement> {
    return <TElement extends Element = Element>(refName: string): RefHook<TElement> => {
        return element.querySelector<TElement>(`[ref="${refName}"]`)
    }
}

/**
 * Standalone useRef function that finds elements by ref attribute within a container
 */
export function useRef<TElement extends Element = Element>(
    refName: string,
    container: Element = document.body,
): RefHook<TElement> {
    return container.querySelector<TElement>(`[ref="${refName}"]`)
}

// ============================================================================
// COMPONENT INSTANCE CREATION
// ============================================================================

/**
 * Create component instance with proper cleanup tracking
 */
function createComponentInstance<T extends HTMLElement>(
    element: T,
    reactive = false,
    componentId: string,
): ComponentInstance<T> {
    // Return cached instance if available (for non-reactive components)
    if (!reactive && componentInstanceCache.has(element)) {
        const cached = componentInstanceCache.get(element)
        return cached as ComponentInstance<T>
    }

    const props = extractProps(element, reactive)
    let stateCounter = 0

    // Create bound methods once
    const querySelector = element.querySelector.bind(element)
    const querySelectorAll = element.querySelectorAll.bind(element)

    const useState = <StateType>(initialValue: StateType): StateHook<StateType> => {
        const stateKey = `${componentId}-${stateCounter++}`
        return createStateHook(element, stateKey, initialValue)
    }

    const useRef = createRefHook(element)

    const instance: ComponentInstance<T> = {
        element,
        props,
        querySelector,
        querySelectorAll,
        useRef,
        useState,
    }

    // Cache instance for non-reactive components
    if (!reactive) {
        componentInstanceCache.set(element, instance)
    }

    return instance
}

// ============================================================================
// COMPONENT INITIALIZATION
// ============================================================================

/**
 * Initialize components with proper error handling and cleanup
 */
export function initializeComponents<T extends HTMLElement = HTMLElement>(
    selector: string,
    callback: ComponentCallback<T>,
    reactive = false,
    componentId = 'anonymous',
): CleanupFunction[] {
    const elements = document.querySelectorAll<T>(selector)
    const cleanupFunctions: CleanupFunction[] = []

    for (let i = 0; i < elements.length; i++) {
        const element = elements[i]
        const instanceId = `${componentId}-${i}`

        try {
            const componentInstance = createComponentInstance(element, reactive, instanceId)

            const userCleanup = callback(componentInstance)

            // Create comprehensive cleanup function
            const enhancedCleanup: CleanupFunction = () => {
                // Run user cleanup first
                if (typeof userCleanup === 'function') {
                    try {
                        userCleanup()
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

                // Clean up instance cache (WeakMaps handle state cleanup automatically)
                componentInstanceCache.delete(element)
            }

            cleanupFunctions.push(enhancedCleanup)
        } catch (error) {
            console.error(`Error initializing component ${instanceId}:`, error)
        }
    }

    return cleanupFunctions
}

// ============================================================================
// COMPONENT CONFIGURATION AND MOUNTING
// ============================================================================

/**
 * Create a component configuration
 */
export function createComponent<T extends HTMLElement = HTMLElement>(
    id: string,
    selector: string,
    callback: ComponentCallback<T>,
    options: ComponentMountOptions = {},
): ComponentConfig<T> {
    if (!id || typeof id !== 'string') {
        throw new Error('Component id must be a non-empty string')
    }

    if (!selector || typeof selector !== 'string') {
        throw new Error('Component selector must be a non-empty string')
    }

    if (typeof callback !== 'function') {
        throw new Error('Component callback must be a function')
    }

    return {
        callback,
        id,
        reactive: options.reactive ?? false,
        selector,
    }
}

// ============================================================================
// MOUNTING SYSTEM
// ============================================================================

const mountedComponents = new Set<string>()

/**
 * Mount a component with automatic DOM ready and Turbo support
 */
export function mount<T extends HTMLElement = HTMLElement>(component: ComponentConfig<T>): Promise<CleanupFunction[]> {
    const { id, selector, callback, reactive } = component
    const attrName = `${MOUNTED_ATTR_PREFIX}${id}`
    let cleanupFunctions: CleanupFunction[] = []

    return new Promise(resolve => {
        const initialize = (): void => {
            if (document.body.hasAttribute(attrName)) {
                resolve(cleanupFunctions)
                return
            }

            document.body.setAttribute(attrName, 'true')
            mountedComponents.add(id)
            cleanupFunctions = initializeComponents(selector, callback, reactive, id)
            resolve(cleanupFunctions)
        }

        const cleanup = (): void => {
            if (!document.body.hasAttribute(attrName)) {
                return
            }

            cleanupFunctions.forEach(fn => {
                try {
                    fn()
                } catch (err) {
                    console.warn(`Cleanup error for ${id}:`, err)
                }
            })
            document.body.removeAttribute(attrName)
            mountedComponents.delete(id)
            cleanupFunctions = []
        }

        if (isTurboAvailable()) {
            document.addEventListener('turbo:before-render', cleanup, { passive: true })
            document.addEventListener('turbo:load', initialize, { passive: true })
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initialize, { once: true, passive: true })
        } else {
            setTimeout(initialize, 0)
        }
    })
}

/**
 * Create and mount a component in one step
 */
export function createComponentAndMount<T extends HTMLElement = HTMLElement>(
    id: string,
    selector: string,
    callback: ComponentCallback<T>,
    options: ComponentMountOptions = {},
): Promise<CleanupFunction[]> {
    const component = createComponent(id, selector, callback, options)
    return mount(component)
}

/**
 * Create a single component instance for the first matching element
 */
export function createSingleComponent<T extends HTMLElement = HTMLElement>(
    selector: string,
    callback: ComponentCallback<T>,
): CleanupFunction | undefined {
    const element = document.querySelector<T>(selector)

    if (!element) {
        console.warn(`No element found for selector: ${selector}`)
        return
    }

    const componentInstance = createComponentInstance(element, false, 'single-component')
    return callback(componentInstance)
}
