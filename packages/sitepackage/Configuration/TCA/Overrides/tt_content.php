<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

$contentElements = [
    'mc_hero' => [
        'title' => 'Hero Section',
        'description' => 'Hero section with background image, title, and CTA',
        'icon' => 'content-header',
    ],
    'mc_intro' => [
        'title' => 'Intro Section',
        'description' => 'Introduction section with values and quote',
        'icon' => 'content-text',
    ],
    'mc_cta' => [
        'title' => 'Call to Action',
        'description' => 'Call to action section with button',
        'icon' => 'actions-play',
    ],
    'mc_faq' => [
        'title' => 'FAQ Accordion',
        'description' => 'Frequently asked questions with accordion',
        'icon' => 'content-menu-abstract',
    ],
    'mc_journey' => [
        'title' => 'Journey Steps',
        'description' => 'Step-by-step journey visualization',
        'icon' => 'content-timeline',
    ],
    'mc_testimonials' => [
        'title' => 'Testimonials',
        'description' => 'Testimonials grid from database',
        'icon' => 'content-quote',
    ],
    'mc_moderator' => [
        'title' => 'Moderator Profile',
        'description' => 'Moderator section with photo and bio',
        'icon' => 'content-user',
    ],
    'mc_newsletter' => [
        'title' => 'Newsletter Signup',
        'description' => 'Newsletter subscription form',
        'icon' => 'content-form',
    ],
    'mc_text_section' => [
        'title' => 'Text Section',
        'description' => 'Text section with eyebrow and rich content',
        'icon' => 'content-text',
    ],
    'mc_values' => [
        'title' => 'Value Items',
        'description' => 'Value items list with numbers and descriptions',
        'icon' => 'content-list',
    ],
    'mc_whatsapp' => [
        'title' => 'WhatsApp Community',
        'description' => 'WhatsApp community call-to-action section',
        'icon' => 'content-link',
    ],
];

foreach ($contentElements as $cType => $config) {
    ExtensionManagementUtility::addTcaSelectItem(
        'tt_content',
        'CType',
        [
            'label' => $config['title'],
            'value' => $cType,
            'icon' => $config['icon'],
            'group' => 'sitepackage',
        ],
    );

    $GLOBALS['TCA']['tt_content']['types'][$cType] = [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                header;Title,
                subheader;Subtitle,
                bodytext;Text,
                pi_flexform;Settings,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:media,
                assets,
                image,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
        ',
        'columnsOverrides' => [
            'bodytext' => [
                'config' => [
                    'enableRichtext' => true,
                ],
            ],
        ],
    ];

    ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:sitepackage/Configuration/FlexForms/' . $cType . '.xml',
        $cType,
    );
}

ExtensionManagementUtility::addTcaSelectItemGroup(
    'tt_content',
    'CType',
    'sitepackage',
    'Mens Circle',
);

$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['enableRichtext'] = true;
