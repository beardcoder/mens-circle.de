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
                header,
                subheader,
                bodytext,
            --div--;Settings,
                pi_flexform,
            --div--;Access,
                --palette--;;hidden,
        ',
        'columnsOverrides' => [
            'header' => ['label' => 'Title'],
            'subheader' => ['label' => 'Eyebrow'],
            'bodytext' => ['config' => ['enableRichtext' => true]],
        ],
    ];

    ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:sitepackage/Configuration/FlexForms/mc_cta.xml', $cType);
})();
