<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

/*
 * Register content element group for Mens Circle
 */
(static function (): void {
    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        'menscircle',
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:content_group.menscircle',
        'after:default',
    );

    $newColumns = [
        'tx_sitepackage_eyebrow' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_eyebrow',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 100,
                'eval' => 'trim',
            ],
        ],
        'tx_sitepackage_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_title',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 2,
            ],
        ],
        'tx_sitepackage_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_text',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 4,
            ],
        ],
        'tx_sitepackage_quote' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_quote',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 3,
            ],
        ],
        'tx_sitepackage_button_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_button_text',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'tx_sitepackage_button_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_button_link',
            'config' => [
                'type' => 'link',
            ],
        ],
        'tx_sitepackage_subtitle' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_subtitle',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 2,
            ],
        ],
        'tx_sitepackage_name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_name',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 2,
            ],
        ],
        'tx_sitepackage_background_image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_background_image',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'common-image-types',
            ],
        ],
        'tx_sitepackage_photo' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.tx_sitepackage_photo',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'common-image-types',
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns('tt_content', $newColumns);
})();
