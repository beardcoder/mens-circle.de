<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to build HTML class attributes similar to classcat/clsx.
 *
 * Examples:
 *
 * Inline usage:
 * <div class="{f:class(value: 'base-class active')}">
 * <div class="{f:class(value: {base: 'card', active: isActive})}">
 *
 * Tag-based usage:
 * <f:class value="card card-bordered" />
 *
 * Store in variable:
 * <f:class value="my-class another-class" name="myClasses" />
 */
final class ClassViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument(
            'value',
            'mixed',
            'The classes to process. Can be a string, array, or object',
        );

        $this->registerArgument(
            'name',
            'string',
            'Optional variable name to store the result instead of returning it',
        );
    }

    public function render(): string
    {
        $value = $this->arguments['value'] ?? $this->renderChildren() ?? '';
        $name = $this->arguments['name'] ?? null;

        // Process classes and build result
        $classes = self::extractClasses($value);
        $result = implode(' ', array_keys($classes));

        // If name is provided, store in variable and return empty string
        if ($name !== null && $name !== '') {
            $this->renderingContext->getVariableProvider()->add($name, $result);

            return '';
        }

        return $result;
    }

    /**
     * Extract classes from various input types and deduplicate.
     */
    private static function extractClasses(mixed $value): array
    {
        $classes = [];
        self::processValue($value, $classes);

        return $classes;
    }

    /**
     * Recursively process values and add to classes array.
     */
    private static function processValue(mixed $value, array &$classes): void
    {
        if ($value === null || $value === false || $value === '') {
            return; // ignore empty-ish values
        }

        match (true) {
            \is_string($value) || $value instanceof \Stringable => self::addClassesFromString($value, $classes),
            \is_array($value) || $value instanceof \Traversable => self::addClassesFromIterable($value, $classes),
            \is_object($value) => self::addClassesFromObject($value, $classes),
            default => self::addScalar($value, $classes),
        };
    }

    /**
     * Normalize and split a string of classes, adding them to the list.
     */
    private static function addClassesFromString(string $value, array &$classes): void
    {
        $value = preg_replace('/\s+/', ' ', trim($value)); // normalize whitespace
        if ($value === '' || $value === null) {
            return;
        }
        foreach (explode(' ', $value) as $class) {
            if ($class !== '') {
                $classes[$class] = true;
            }
        }
    }

    /**
     * Iterate over an iterable value, handling associative truthy flags and nested structures.
     * Accepts both arrays and Traversable instances.
     */
    private static function addClassesFromIterable(iterable $values, array &$classes): void
    {
        foreach ($values as $key => $item) {
            if (\is_string($key) && !is_numeric($key)) {
                if ($item) {
                    $classes[$key] = true;
                }
                continue;
            }
            // nested value, recurse
            self::processValue($item, $classes);
        }
    }

    /**
     * Handle object values by using __toString when available, otherwise public properties.
     */
    private static function addClassesFromObject(object $value, array &$classes): void
    {
        self::addClassesFromIterable(get_object_vars($value), $classes);
    }

    /**
     * Fallback for other scalar types (int, float, etc.).
     */
    private static function addScalar(mixed $value, array &$classes): void
    {
        $classes[(string) $value] = true;
    }
}
