<?php

declare(strict_types=1);

return [
    'frontend' => [
        'menscircle/ajax-form-handler' => [
            'target' => \MensCircle\Sitepackage\Middleware\AjaxFormMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/site',
            ],
        ],
    ],
];
