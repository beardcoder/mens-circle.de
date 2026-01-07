<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Middleware\NewsletterMiddleware;
use MensCircle\Sitepackage\Middleware\AjaxFormMiddleware;

return [
    'frontend' => [
        'menscircle/newsletter-handler' => [
            'target' => NewsletterMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/site',
            ],
        ],
        'menscircle/ajax-form-handler' => [
            'target' => AjaxFormMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'menscircle/newsletter-handler',
            ],
        ],
    ],
];
