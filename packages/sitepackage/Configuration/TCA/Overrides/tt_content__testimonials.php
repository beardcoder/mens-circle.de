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
 * Testimonials Content Element
 * 
 * Testimonials carousel/grid displaying testimonials from database.
 * Automatically fetches published testimonials via data processing.
 */
(static function (): void {
    $cType = 'mc_testimonials';

    // Register content element
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_testimonials',
            'value' => $cType,
            'icon' => 'content-quote',
            'group' => 'menscircle',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tt_content.mc_testimonials.description',
        ],
    );

    // Define TCA configuration
    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;core.form.tabs:general,
                --palette--;;general,
            --div--;core.form.tabs:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;core.form.tabs:access,
                --palette--;;hidden,
                --palette--;;access,
        ',
    ];
})();
