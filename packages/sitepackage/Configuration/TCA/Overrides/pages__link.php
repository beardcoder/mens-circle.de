<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(static function (): void {
    $extensionKey = 'sitepackage';
    $customPageDoktype = 1759827239;
    $customIconClass = 'tx-sitepackage-page-link';

    $dokTypeRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry::class);
    $dokTypeRegistry->add(
        $customPageDoktype,
        [
            'allowedTables' => '*',
        ],
    );

    // Add the new doktype to the page type selector
    ExtensionManagementUtility::addTcaSelectItem(
        'pages',
        'doktype',
        [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:page.link',
            'value' => $customPageDoktype,
            'icon' => $customIconClass,
            'group' => $extensionKey,
        ],
    );

    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$customPageDoktype] = $customIconClass;

    foreach (['contentFromPid', 'hideinmenu', 'root'] as $suffix) {
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$customPageDoktype.'-'.$suffix] = $customIconClass.'-'.$suffix;
    }

    $columnArray = [
        'content_link' => [
            'exclude' => true,
            'label' => 'Inhalt verlinken',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['page', 'url', 'tt_content'],
            ],
        ],
    ];

    $GLOBALS['TCA']['pages']['types'][$customPageDoktype] = $GLOBALS['TCA']['pages']['types'][1];

    ExtensionManagementUtility::addTCAcolumns('pages', $columnArray);
    ExtensionManagementUtility::addToAllTCAtypes('pages', 'content_link', '1759827239', 'after:subtitle');
});
