<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(static function (): void {
    $extensionKey = 'sitepackage';
    $customPageDoktype = 1724352888;
    $customIconClass = 'tx-sitepackage-page-default';

    // Add the new doktype to the page type selector
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:page.default',
            'value' => $customPageDoktype,
            'icon' => $customIconClass,
            'group' => $extensionKey,
        ],
    );

    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$customPageDoktype] = $customIconClass;

    foreach (['contentFromPid', 'hideinmenu', 'root'] as $suffix) {
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']["{$customPageDoktype}-{$suffix}"] = "{$customIconClass}-{$suffix}";
    }
});
