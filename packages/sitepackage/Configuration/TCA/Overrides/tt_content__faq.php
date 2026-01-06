<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_faq';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'FAQ',
        'value' => $cType,
        'icon' => 'content-menu-abstract',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
                tx_sitepackage_text,
            --div--;Items,
                pi_flexform,
            --div--;Access,
                --palette--;;hidden,
        ',
        'columnsOverrides' => [
            'tx_sitepackage_text' => ['label' => 'Intro'],
        ],
    ];

    ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:sitepackage/Configuration/FlexForms/mc_faq.xml', $cType);
})();
