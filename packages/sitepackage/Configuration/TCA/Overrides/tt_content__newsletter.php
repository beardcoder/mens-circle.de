<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_newsletter';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Newsletter',
        'value' => $cType,
        'icon' => 'content-form',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
                tx_sitepackage_text,
            --div--;Access,
                --palette--;;hidden,
        ',
    ];
})();
