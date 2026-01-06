<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_values';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Value Items',
        'value' => $cType,
        'icon' => 'content-bullets',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
            --div--;Items,
                pi_flexform,
            --div--;Access,
                --palette--;;hidden,
        ',
    ];

    ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:sitepackage/Configuration/FlexForms/mc_values.xml', $cType);
})();
