<?php

declare(strict_types=1);

/*
 * FrankenPHP Worker Script for TYPO3
 *
 * This worker keeps PHP running between requests, significantly reducing
 * bootstrap overhead and improving response times.
 *
 * Important: This requires FrankenPHP worker mode to be enabled in the Caddyfile.
 * Uncomment the worker line in Caddyfile:
 *   worker /var/www/html/public/worker.php
 */

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\Application;

// Resolve composer autoload
$classLoader = require dirname(__DIR__) . '/vendor/autoload.php';

// Check if running in worker mode
if (!function_exists('frankenphp_handle_request')) {
    // Fallback to standard request handling (same as index.php)
    call_user_func(static function () use ($classLoader): void {
        SystemEnvironmentBuilder::run();
        $container = Bootstrap::init($classLoader, false);
        if ($container->has(Application::class)) {
            $container->get(Application::class)->run();
        }
    });
    return;
}

// Keep worker alive, set max requests to prevent memory leaks
$maxRequests = (int) ($_SERVER['FRANKENPHP_MAX_REQUESTS'] ?? 1000);
$requestCount = 0;

// Worker loop - handles requests continuously
while ($requestCount < $maxRequests && frankenphp_handle_request(function () use ($classLoader): void {
    try {
        // Run TYPO3 for each request
        SystemEnvironmentBuilder::run();
        $container = Bootstrap::init($classLoader, false);

        if ($container->has(Application::class)) {
            $container->get(Application::class)->run();
        }
    } catch (\Throwable $e) {
        // Log error but continue processing requests
        error_log('TYPO3 Worker Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        // Send error response
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain');
        }
        echo 'Internal Server Error';
    }
})) {
    $requestCount++;

    // Periodic garbage collection
    if ($requestCount % 100 === 0 && function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
}

