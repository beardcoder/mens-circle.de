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
                tx_sitepackage_eyebrow,
                tx_sitepackage_name,
                bodytext,
                tx_sitepackage_quote,
                tx_sitepackage_photo,
            --div--;Access,
                --palette--;;hidden,
        ',
        'columnsOverrides' => [
            'bodytext' => ['config' => ['enableRichtext' => true], 'label' => 'Bio'],
        ],
    ];
})();
