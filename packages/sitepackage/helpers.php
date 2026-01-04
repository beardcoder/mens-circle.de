<?php

declare(strict_types=1);

/**
 * Global helper functions for TYPO3 v14
 * Autoloaded via composer.json
 */

use MensCircle\Sitepackage\Utility\Helpers;

if (!function_exists('env')) {
    /**
     * Get environment variable with type casting and default value
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Helpers::env($key, $default);
    }
}

if (!function_exists('is_production')) {
    /**
     * Check if application is in production mode
     */
    function is_production(): bool
    {
        return Helpers::isProduction();
    }
}

if (!function_exists('is_development')) {
    /**
     * Check if application is in development mode
     */
    function is_development(): bool
    {
        return Helpers::isDevelopment();
    }
}

if (!function_exists('app_url')) {
    /**
     * Get the application URL
     */
    function app_url(): string
    {
        return Helpers::appUrl();
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die (development only)
     */
    function dd(mixed ...$vars): never
    {
        Helpers::dd(...$vars);
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get absolute path to public directory
     */
    function resource_path(string $path = ''): string
    {
        return Helpers::resourcePath($path);
    }
}

if (!function_exists('var_path')) {
    /**
     * Get absolute path to var directory
     */
    function var_path(string $path = ''): string
    {
        return Helpers::varPath($path);
    }
}

if (!function_exists('is_ajax')) {
    /**
     * Check if current request is AJAX
     */
    function is_ajax(): bool
    {
        return Helpers::isAjax();
    }
}

if (!function_exists('client_ip')) {
    /**
     * Get client IP address
     */
    function client_ip(): string
    {
        return Helpers::clientIp();
    }
}

if (!function_exists('app_log')) {
    /**
     * Simple logging
     */
    function app_log(string $message, string $level = 'info', string $channel = 'app'): void
    {
        Helpers::log($message, $level, $channel);
    }
}
