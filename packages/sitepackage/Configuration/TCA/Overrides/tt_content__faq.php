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
    $signature = static fn (string $plugin): string => strtolower(
        sprintf('%s_%s', str_replace('_', '', $extensionKey), $plugin),
    );

    $key = $signature('Faq');

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content.faq',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content.faq.description',
            'value' => $key,
            'icon' => 'tx-sitepackage-content-faq',
            'group' => $extensionKey,
        ],
        '
        header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories',
    );
});
