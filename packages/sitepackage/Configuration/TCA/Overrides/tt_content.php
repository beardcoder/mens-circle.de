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
 * Register custom content element group and fields for Mens Circle
 * TYPO3 v14 - Modern TCA configuration with PHP 8.5
 */
(static function (): void {
    // Register content element group
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'menscircle',
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content_group.menscircle',
        'after:default',
    );

    // Define custom fields
    // Note: Using standard 'header', 'subheader', and 'bodytext' fields wherever possible
    // Only adding truly custom fields that don't overlap with TYPO3 core
    $customColumns = [
        'tx_sitepackage_button_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_button_text',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
                'required' => false,
            ],
        ],
        'tx_sitepackage_button_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_button_link',
            'config' => [
                'type' => 'link',
                'required' => false,
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns('tt_content', $customColumns);
})();
