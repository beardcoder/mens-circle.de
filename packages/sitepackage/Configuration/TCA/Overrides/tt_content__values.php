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
 * Value Items Content Element
 * 
 * Grid of value/benefit items with icons and descriptions.
 * Uses FlexForm for repeatable value items.
 */
(static function (): void {
    $cType = 'mc_values';

    // Register content element
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_values',
            'value' => $cType,
            'icon' => 'content-bullets',
            'group' => 'menscircle',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_values.description',
        ],
    );

    // Define TCA configuration
    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                tx_sitepackage_subheader,
                header,
            --div--;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tab.value_items,
                pi_flexform,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
        ',
    ];

    // Register FlexForm
    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:sitepackage/Configuration/FlexForms/mc_values.xml',
        $cType
    );
})();
