<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_moderator';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Moderator',
        'value' => $cType,
        'icon' => 'content-user',
        'group' => 'menscircle',
    ]);

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
            'header' => ['label' => 'Name'],
            'bodytext' => ['config' => ['enableRichtext' => true], 'label' => 'Bio'],
            'assets' => ['config' => ['maxitems' => 1], 'label' => 'Photo'],
        ],
    ];

    ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:sitepackage/Configuration/FlexForms/mc_moderator.xml', $cType);
})();
