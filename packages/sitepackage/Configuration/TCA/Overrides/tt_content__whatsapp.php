<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_whatsapp';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'WhatsApp Community',
        'value' => $cType,
        'icon' => 'content-link',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
                tx_sitepackage_eyebrow,
                tx_sitepackage_title,
                bodytext,
                tx_sitepackage_button_text,
                tx_sitepackage_button_link,
                tx_sitepackage_text,
            --div--;Access,
                --palette--;;hidden,
        ',
        'columnsOverrides' => [
            'bodytext' => ['config' => ['enableRichtext' => true]],
            'tx_sitepackage_text' => ['label' => 'Hint'],
        ],
    ];
})();
