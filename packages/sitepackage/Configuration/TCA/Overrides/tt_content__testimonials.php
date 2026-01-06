<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $cType = 'mc_testimonials';

    ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
        'label' => 'Testimonials',
        'value' => $cType,
        'icon' => 'content-quote',
        'group' => 'menscircle',
    ]);

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;General,
            --div--;Access,
                --palette--;;hidden,
        ',
    ];
})();
