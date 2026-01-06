<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_journey';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Journey Steps',
        'value' => $cType,
        'icon' => 'content-timeline',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
                tx_sitepackage_subtitle,
            --div--;Steps,
                pi_flexform,
            --div--;Access,
                --palette--;;hidden,
        ',
    ];

    ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:sitepackage/Configuration/FlexForms/mc_journey.xml', $cType);
})();
