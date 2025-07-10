<?php

declare (strict_types = 1);

use MensCircle\Sitepackage\Middleware\EventApiMiddleware;

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
    ],
];
