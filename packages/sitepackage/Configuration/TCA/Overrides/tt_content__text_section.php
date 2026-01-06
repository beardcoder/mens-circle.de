<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_text_section';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Text Section',
        'value' => $cType,
        'icon' => 'content-text',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
                bodytext,
            --div--;Access,
                --palette--;;hidden,
        ',
        'columnsOverrides' => [
            'bodytext' => ['config' => ['enableRichtext' => true], 'label' => 'Content'],
        ],
    ];
})();
