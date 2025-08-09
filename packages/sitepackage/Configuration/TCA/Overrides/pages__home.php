<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(static function (): void {
    $extensionKey = 'sitepackage';
    $customPageDoktype = 1724352539;
    $customIconClass = 'tx-sitepackage-page-home';

    // Add the new doktype to the page type selector
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:page.home',
            'value' => $customPageDoktype,
            'icon'  => $customIconClass,
            'group' => $extensionKey,
        ],
    );

    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$customPageDoktype] = $customIconClass;
});
