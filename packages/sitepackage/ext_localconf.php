<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Controller\EventController;
use MensCircle\Sitepackage\Controller\SubscriptionController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    $extensionKey = 'sitepackage';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets'][$extensionKey] = 'EXT:sitepackage/Configuration/RTE/Default.yaml';

    ExtensionUtility::configurePlugin(
        ucfirst($extensionKey),
        'EventList',
        [
            EventController::class => 'list',
        ],
        [],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    ExtensionUtility::configurePlugin(
        ucfirst($extensionKey),
        'EventDetail',
        [
            EventController::class => ['detail', 'registration', 'upcoming'],
        ],
        [
            EventController::class => ['registration', 'upcoming'],
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    ExtensionUtility::configurePlugin(
        ucfirst($extensionKey),
        'Newsletter',
        [
            SubscriptionController::class => ['form', 'subscribe', 'doubleOptIn', 'unsubscribe'],
        ],
        [
            SubscriptionController::class => ['subscribe', 'doubleOptIn', 'unsubscribe'],
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    );

    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend'] = [
        'backendLogo' => 'EXT:sitepackage/Resources/Public/Images/logo-white.svg',
        'loginBackgroundImage' => 'EXT:sitepackage/Resources/Public/Images/Background.jpg',
        'loginLogo' => 'EXT:sitepackage/Resources/Public/Images/logo-small.png',
        'loginHighlightColor' => '#b76f2b',
        'loginFootnote' => '© 2023-2024 Build with ❤️ and mindfulness in Bavaria',
    ];

  ExtensionManagementUtility::addTypoScriptSetup('
        plugin.tx_form {
          settings {
            yamlConfigurations {
              1737049457 = EXT:sitepackage/Configuration/Form/CustomFormSetup.yaml
            }
          }
        }

        module.tx_sitepackage_newsletter {
          view {
            templateRootPaths.100 = EXT:sitepackage/Resources/Private/Templates/Backend/Newsletter/
            partialRootPaths.100 = EXT:sitepackage/Resources/Private/Partials/Backend/Newsletter/
            layoutRootPaths.100 = EXT:sitepackage/Resources/Private/Layouts/Backend/Newsletter/
          }
        }

    ');
})();
