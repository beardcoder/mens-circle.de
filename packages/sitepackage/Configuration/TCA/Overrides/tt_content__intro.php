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
 * Intro Section Content Element
 *
 * Two-column introduction section with heading, text, statistics/values, and quote.
 * Uses FlexForm for repeatable value items with numbers and descriptions.
 */
(static function (): void {
    $cType = 'mc_intro';

    // Register content element
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_intro',
            'value' => $cType,
            'icon' => 'content-text',
            'group' => 'menscircle',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_intro.description',
        ],
    );


    $GLOBALS['TCA']['tt_content']['columns']['tx_sitepackage_quote'] = [
        'exclude' => true,
        'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.header_link.intro',
        'config' => [
            'type' => 'text',
        ],
    ];

    // Define TCA configuration
    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;core.form.tabs:general,
                --palette--;;general,
                subheader,
                header,
                bodytext;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.bodytext.intro,
                tx_sitepackage_quote,
            --div--;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tab.values,
                pi_flexform,
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
                ],
            ],
            'bodytext' => [
                'config' => [
                    'enableRichtext' => false,
                    'rows' => 4,
                ],
            ],
        ],
    ];

    // Register FlexForm
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:sitepackage/Configuration/FlexForms/mc_intro.xml',
        $cType
    );
})();
