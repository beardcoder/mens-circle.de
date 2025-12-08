<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(static function (): void {
    $extensionKey = 'sitepackage';
    $signature = static fn (string $plugin): string => strtolower(
        sprintf('%s_%s', str_replace('_', '', $extensionKey), $plugin),
    );

    $key = $signature('Header');

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content.header',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content.header.description',
            'value' => $key,
            'icon' => 'tx-sitepackage-content-header',
            'group' => $extensionKey,
        ],
        '
        header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
        header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel,
        bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories',
        [
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'enableRichtext' => true,
                    ],
                ],
            ],
        ]
    );
});
