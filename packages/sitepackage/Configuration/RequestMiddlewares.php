<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use MensCircle\Sitepackage\Middleware\EventApiMiddleware;
use MensCircle\Sitepackage\Middleware\ResponseCacheMiddleware;
use MensCircle\Sitepackage\Middleware\SentryTracingMiddleware;

return [
    'frontend' => [
        'mens-circle/sitepackage/sentry-tracing' => [
            'target' => SentryTracingMiddleware::class,
            'before' => ['typo3/cms-frontend/timetracker'],
        ],
        'mens-circle/sitepackage/response-cache' => [
            'target' => ResponseCacheMiddleware::class,
            'after' => ['mens-circle/sitepackage/sentry-tracing'],
            'before' => ['typo3/cms-frontend/page-resolver'],
        ],
        'mens-circle/sitepackage/event' => [
            'target' => EventApiMiddleware::class,
            'before' => ['typo3/cms-frontend/page-resolver'],
            'after' => ['typo3/cms-frontend/eid'],
        ],
    ],
    'backend' => [
        'mens-circle/sitepackage/sentry-tracing' => [
            'target' => SentryTracingMiddleware::class,
            'before' => ['typo3/cms-core/normalized-params-attribute'],
        ],
    ],
];
