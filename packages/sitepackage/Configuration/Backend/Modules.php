<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use MensCircle\Sitepackage\Backend\Controller\EventNotificationController;
use MensCircle\Sitepackage\Backend\Controller\NewsletterController;

return [
    'events' => [
        'labels' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_mod_events.xlf',
        'iconIdentifier' => 'event-module',
        'position' => [
            'after' => 'web',
        ],
    ],
    'events_notification' => [
        'parent' => 'events',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/sitepackage/eventNotification',
        'labels' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_mod_notifications.xlf',
        'extensionName' => 'Sitepackage',
        'iconIdentifier' => 'event-notification-module',
        'controllerActions' => [
            EventNotificationController::class => ['list', 'new', 'send'],
        ],
    ],
    'newsletter' => [
        'parent' => 'events',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/sitepackage/newsletter',
        'labels' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_mod_newsletter.xlf',
        'extensionName' => 'Sitepackage',
        'iconIdentifier' => 'newsletter-module',
        'controllerActions' => [
            NewsletterController::class => ['new', 'send'],
        ],
    ],
];
