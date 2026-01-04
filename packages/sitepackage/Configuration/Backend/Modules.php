<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Controller\Backend\NewsletterController;

return [
    'menscircle_newsletter' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/menscircle/newsletter',
        'iconIdentifier' => 'module-newsletter',
        'labels' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_mod_newsletter.xlf',
        'routes' => [
            '_default' => [
                'target' => NewsletterController::class . '::indexAction',
            ],
            'subscribers' => [
                'path' => '/subscribers',
                'target' => NewsletterController::class . '::subscribersAction',
            ],
            'create' => [
                'path' => '/create',
                'target' => NewsletterController::class . '::createAction',
            ],
            'edit' => [
                'path' => '/edit/{newsletter}',
                'target' => NewsletterController::class . '::editAction',
            ],
            'save' => [
                'path' => '/save',
                'methods' => ['POST'],
                'target' => NewsletterController::class . '::saveAction',
            ],
            'send' => [
                'path' => '/send/{newsletter}',
                'methods' => ['POST'],
                'target' => NewsletterController::class . '::sendAction',
            ],
            'sendTest' => [
                'path' => '/send-test/{newsletter}',
                'methods' => ['POST'],
                'target' => NewsletterController::class . '::sendTestAction',
            ],
            'delete' => [
                'path' => '/delete/{newsletter}',
                'methods' => ['POST'],
                'target' => NewsletterController::class . '::deleteAction',
            ],
        ],
    ],
];
