<?php

declare(strict_types=1);

return [
    'frontend' => [
        'menscircle/newsletter-handler' => [
            'target' => \MensCircle\Sitepackage\Middleware\NewsletterMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/site',
            ],
        ],
        'menscircle/ajax-form-handler' => [
            'target' => \MensCircle\Sitepackage\Middleware\AjaxFormMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'menscircle/newsletter-handler',
            ],
        ],
    ],
];
