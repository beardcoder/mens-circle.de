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
 * Newsletter Signup Content Element
 * 
 * Newsletter subscription form with heading and description.
 * Integrates with custom newsletter subscription functionality.
 */
(static function (): void {
    $cType = 'mc_newsletter';

    // Register content element
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_newsletter',
            'value' => $cType,
            'icon' => 'content-form',
            'group' => 'menscircle',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_newsletter.description',
        ],
    );

    // Define TCA configuration
    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                tx_sitepackage_subheader,
                header,
                bodytext,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
        ',
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => false,
                    'rows' => 3,
                ],
            ],
        ],
    ];
})();
