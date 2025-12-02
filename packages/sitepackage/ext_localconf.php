<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Controller\EventController;
use MensCircle\Sitepackage\Controller\SubscriptionController;
use MensCircle\Sitepackage\Service\SentryService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    $extensionKey = 'sitepackage';

    // Initialize Sentry as early as possible
    try {
        $sentryService = GeneralUtility::makeInstance(SentryService::class);
        $sentryService->initialize();
    } catch (Throwable $e) {
        // Silently fail if Sentry initialization fails to not break the application
    }

    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets'][$extensionKey] = 'EXT:sitepackage/Configuration/RTE/Default.yaml';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['app'] = ['MensCircle\Sitepackage\ViewHelpers'];

    ExtensionUtility::configurePlugin(
        ucfirst($extensionKey),
        'EventList',
        [
            EventController::class => 'list',
        ],
        [],
    );

    ExtensionUtility::configurePlugin(
        ucfirst($extensionKey),
        'EventDetail',
        [
            EventController::class => ['detail', 'registration', 'registrationSuccess', 'upcoming'],
        ],
        [
            EventController::class => ['registration', 'upcoming'],
        ],
    );

    ExtensionUtility::configurePlugin(
        ucfirst($extensionKey),
        'Newsletter',
        [
            SubscriptionController::class => ['form', 'subscribe', 'success', 'doubleOptIn', 'unsubscribe'],
        ],
        [
            SubscriptionController::class => ['subscribe', 'doubleOptIn', 'unsubscribe'],
        ],
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
