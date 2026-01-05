<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Controller\Backend\EventModuleController;

/**
 * Backend module registration for TYPO3 v14
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
        'extensionName' => 'Sitepackage',
        'controllerActions' => [
            EventModuleController::class => [
                'index',
                'list',
                'show',
                'togglePublish',
                'exportRegistrations',
            ],
        ],
    ],
];
