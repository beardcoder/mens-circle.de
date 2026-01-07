<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Controller\Backend\EventModuleController;
use MensCircle\Sitepackage\Controller\Backend\NewsletterController;

/**
 * Backend module registration for TYPO3 v13 LTS
 *
 * @see https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Backend/BackendRouting.html
 */
return [
    'menscircle' => [
        'labels' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab',
        'iconIdentifier' => 'module-menscircle',
        'position' => ['after' => 'web'],
    ],
    'menscircle_events' => [
        'parent' => 'menscircle',
        'position' => ['before' => '*'],
        'access' => 'user',
        'iconIdentifier' => 'module-menscircle-events',
        'labels' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_mod_events.xlf',
        'path' => '/module/menscircle/events',
        'routes' => [
            '_default' => [
                'target' => EventModuleController::class . '::indexAction',
            ],
            'index' => [
                'path' => '/index',
                'target' => EventModuleController::class . '::indexAction',
            ],
            'list' => [
                'path' => '/list',
                'target' => EventModuleController::class . '::listAction',
            ],
            'show' => [
                'path' => '/show',
                'target' => EventModuleController::class . '::showAction',
            ],
            'togglePublish' => [
                'path' => '/toggle-publish',
                'target' => EventModuleController::class . '::togglePublishAction',
            ],
            'exportRegistrations' => [
                'path' => '/export-registrations',
                'target' => EventModuleController::class . '::exportRegistrationsAction',
            ],
        ],
    ],
    'menscircle_newsletter' => [
        'parent' => 'menscircle',
        'position' => ['after' => 'menscircle_events'],
        'access' => 'user',
        'iconIdentifier' => 'module-menscircle-newsletter',
        'labels' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_mod_newsletter.xlf',
        'path' => '/module/menscircle/newsletter',
        'routes' => [
            '_default' => [
                'target' => NewsletterController::class . '::indexAction',
            ],
            'index' => [
                'path' => '/index',
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
                'path' => '/edit',
                'target' => NewsletterController::class . '::editAction',
            ],
            'save' => [
                'path' => '/save',
                'target' => NewsletterController::class . '::saveAction',
            ],
            'send' => [
                'path' => '/send',
                'target' => NewsletterController::class . '::sendAction',
            ],
            'sendTest' => [
                'path' => '/send-test',
                'target' => NewsletterController::class . '::sendTestAction',
            ],
            'delete' => [
                'path' => '/delete',
                'target' => NewsletterController::class . '::deleteAction',
            ],
        ],
    ],
];
