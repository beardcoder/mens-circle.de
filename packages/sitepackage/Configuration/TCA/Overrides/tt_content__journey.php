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
 * Journey Steps Content Element
 * 
 * Step-by-step journey visualization with numbered steps.
 * Uses FlexForm for repeatable step items with icons, headings, and descriptions.
 */
(static function (): void {
    $cType = 'mc_journey';

    // Register content element
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_journey',
            'value' => $cType,
            'icon' => 'content-timeline',
            'group' => 'menscircle',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_journey.description',
        ],
    );

    // Define TCA configuration
    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                tx_sitepackage_subheader,
                header,
                bodytext;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.bodytext.journey,
            --div--;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tab.journey_steps,
                pi_flexform,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
        ',
        'columnsOverrides' => [
            'bodytext' => [
                'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.bodytext.journey',
                'config' => [
                    'enableRichtext' => false,
                    'rows' => 2,
                ],
            ],
        ],
    ];

    // Register FlexForm
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:sitepackage/Configuration/FlexForms/mc_journey.xml',
        $cType
    );
})();
