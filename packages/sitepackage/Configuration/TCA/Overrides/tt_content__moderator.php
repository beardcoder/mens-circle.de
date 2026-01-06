<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "sitepackage" by Markus Sommer.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

/**
 * Moderator Profile Content Element
 * 
 * Moderator/team member profile with photo, name, bio, and optional quote.
 * Uses standard TYPO3 fields plus image upload.
 */
(static function (): void {
    $cType = 'mc_moderator';

    // Register content element
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_moderator',
            'value' => $cType,
            'icon' => 'content-user',
            'group' => 'menscircle',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_moderator.description',
        ],
    );

    // Define TCA configuration
    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                tx_sitepackage_subheader,
                header;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.header.moderator,
                bodytext;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.bodytext.moderator,
                header_link;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.header_link.moderator,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:images,
                assets,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
        ',
        'columnsOverrides' => [
            'header' => [
                'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.header.moderator',
                'config' => [
                    'required' => true,
                ],
            ],
            'bodytext' => [
                'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.bodytext.moderator',
                'config' => [
                    'enableRichtext' => true,
                ],
            ],
            'header_link' => [
                'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.header_link.moderator',
                'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.header_link.moderator.description',
            ],
            'assets' => [
                'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.assets.moderator',
                'config' => [
                    'maxitems' => 1,
                ],
            ],
        ],
    ];
})();
