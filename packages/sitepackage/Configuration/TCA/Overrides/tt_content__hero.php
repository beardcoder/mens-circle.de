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
                header,
                bodytext,
                assets,
            --div--;Settings,
                pi_flexform,
            --div--;Access,
                --palette--;;hidden,
        ',
        'columnsOverrides' => [
            'header' => ['label' => 'Title'],
            'bodytext' => ['config' => ['enableRichtext' => true], 'label' => 'Text'],
            'assets' => ['config' => ['maxitems' => 1], 'label' => 'Background Image'],
        ],
    ];

    ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:sitepackage/Configuration/FlexForms/mc_hero.xml', $cType);
})();
