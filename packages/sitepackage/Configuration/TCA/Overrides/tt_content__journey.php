<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(static function (): void {
    $extensionKey = 'sitepackage';
    $signature = static fn (string $plugin): string => strtolower(sprintf('%s_%s', str_replace('_', '', $extensionKey), $plugin));

    $key = $signature('Journey');

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content.journey',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content.journey.description',
            'value' => $key,
            'icon' => 'tx-sitepackage-content-journey',
            'group' => $extensionKey,
        ],
        '
        layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
        subheader;Label,
        header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
        assets,
        pi_flexform',
        [
            'columnsOverrides' => [
                'pi_flexform' => [
                    'config' => [
                        'ds' => 'FILE:EXT:sitepackage/Configuration/FlexForm/Journey.xml',
                    ],
                ],
            ],
        ]
    );
});
