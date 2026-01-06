<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_cta';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Call to Action',
        'value' => $cType,
        'icon' => 'actions-play',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
                tx_sitepackage_text,
                tx_sitepackage_button_text,
                tx_sitepackage_button_link,
            --div--;Access,
                --palette--;;hidden,
        ',
    ];
})();
