<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'module-menscircle' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:sitepackage/Resources/Public/Icons/module-menscircle.svg',
    ],
    'module-menscircle-events' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:sitepackage/Resources/Public/Icons/module-menscircle-events.svg',
    ],
    'module-menscircle-newsletter' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:sitepackage/Resources/Public/Icons/newsletter.svg',
    ],
    'tx-sitepackage-event' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:sitepackage/Resources/Public/Icons/tx_sitepackage_domain_model_event.svg',
    ],
    'tx-sitepackage-eventregistration' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:sitepackage/Resources/Public/Icons/tx_sitepackage_domain_model_eventregistration.svg',
    ],
];
