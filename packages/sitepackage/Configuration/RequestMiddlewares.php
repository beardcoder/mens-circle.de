<?php

declare (strict_types = 1);

use MensCircle\Sitepackage\Middleware\EventApiMiddleware;
use MensCircle\Sitepackage\Middleware\EventFeedMiddleware;

return [
    'frontend' => [
        'mens-circle/sitepackage/event' => [
            'target' => EventApiMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/eid',
            ],
        ],
        'mens-circle/sitepackage/event-feed' => [
            'target' => EventFeedMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/eid',
            ],
        ],
    ],
];
