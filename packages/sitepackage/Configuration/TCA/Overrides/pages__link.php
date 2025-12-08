<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(static function (): void {
    $extensionKey = 'sitepackage';
    $customPageDoktype = 1759827239;
    $customIconClass = 'tx-sitepackage-page-link';

    $pageDoktypeRegistry = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry::class);
    $pageDoktypeRegistry->add(
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
