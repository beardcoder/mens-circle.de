<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

call_user_func(static function (): void {
    $extensionKey = 'sitepackage';
    $signature = static fn (string $plugin): string => strtolower(
        sprintf('%s_%s', str_replace('_', '', $extensionKey), $plugin),
    );

    ExtensionManagementUtility::addTcaSelectItemGroup(
        'tt_content',
        'CType',
        $extensionKey,
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:extension.title',
    );

    ExtensionUtility::registerPlugin(
        ucfirst($extensionKey),
        'EventList',
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:plugin.event_list',
        'tx-sitepackage-plugin-event-list',
        $extensionKey,
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:plugin.event_list.description',
    );
    ExtensionUtility::registerPlugin(
        ucfirst($extensionKey),
        'EventDetail',
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:plugin.event_detail',
        'tx-sitepackage-plugin-event-list',
        $extensionKey,
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:plugin.event_detail.description',
    );

    ExtensionUtility::registerPlugin(
        ucfirst($extensionKey),
        'Newsletter',
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:plugin.newsletter',
        'tx-sitepackage-plugin-newsletter',
        $extensionKey,
        'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:plugin.newsletter.description',
    );

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'pi_flexform',
        implode(',', [$signature('EventList')]),
        'after:header',
    );

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:sitepackage/Configuration/FlexForm/EventList.xml',
        $signature('EventList'),
    );

    $GLOBALS['TCA']['tt_content']['types'][$signature('EventList')] = $GLOBALS['TCA']['tt_content']['types']['header'];
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'pi_flexform, pages',
        $signature('EventList'),
        'after:subheader',
    );
});
