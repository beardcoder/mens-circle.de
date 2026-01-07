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

defined('TYPO3') || die();

/**
 * Hero Section Content Element
 * 
 * Full-width hero section with background image, heading, description, and CTA button.
 * Uses standard TYPO3 fields for maximum compatibility.
 */
(static function (): void {
    $cType = 'mc_hero';

    // Register content element
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_hero',
            'value' => $cType,
            'icon' => 'content-header',
            'group' => 'menscircle',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_hero.description',
        ],
    );

    // Define TCA configuration
    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;core.form.tabs:general,
                --palette--;;general,
                header;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.header.hero,
                subheader;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.subheader.hero,
                bodytext;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.bodytext.hero,
            --div--;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tab.button,
                tx_sitepackage_button_text,
                tx_sitepackage_button_link,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:images,
                assets,
            --div--;core.form.tabs:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;core.form.tabs:access,
                --palette--;;hidden,
                --palette--;;access,
        ',
        'columnsOverrides' => [
            'header' => [
                'config' => [
                    'required' => true,
                    'max' => 150,
                ],
            ],
            'subheader' => [
                'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.subheader.hero',
            ],
            'bodytext' => [
                'config' => [
                    'enableRichtext' => false,
                    'rows' => 3,
                ],
            ],
            'assets' => [
                'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.assets.hero',
                'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.assets.hero.description',
                'config' => [
                    'maxitems' => 1,
                    'overrideChildTca' => [
                        'columns' => [
                            'crop' => [
                                'config' => [
                                    'cropVariants' => [
                                        'default' => [
                                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
                                            'allowedAspectRatios' => [
                                                '16_9' => [
                                                    'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                                                    'value' => 16 / 9,
                                                ],
                                            ],
                                            'selectedRatio' => '16_9',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
})();
