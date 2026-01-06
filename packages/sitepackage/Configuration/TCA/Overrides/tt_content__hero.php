<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_hero';

    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => 'Hero',
            'value' => $cType,
            'icon' => 'content-header',
            'group' => 'menscircle',
        ],
    );

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
                tx_sitepackage_text,
                tx_sitepackage_button_text,
                tx_sitepackage_button_link,
                tx_sitepackage_background_image,
            --div--;Access,
                --palette--;;hidden,
        ',
        'columnsOverrides' => [
            'tx_sitepackage_text' => ['label' => 'Description'],
        ],
    ];
})();
