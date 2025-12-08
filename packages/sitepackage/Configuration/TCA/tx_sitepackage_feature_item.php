<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'label' => 'header',
        'label_alt' => 'subheader',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'hidden' => 'hidden',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:feature_item',
        'hideTable' => true,
        'hideAtCopy' => true,
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'typeicon_classes' => [
            'default' => 'tx-sitepackage-content-feature',
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,
                layout,
                bodytext,
                assets,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
                --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
                --palette--;;hiddenLanguagePalette,
            ',
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => '
                starttime;core.db.general:starttime,
                endtime;core.db.general:endtime
            ',
        ],
        'general' => [
            'showitem' => '
                tt_content
            ',
        ],
        'headers' => [
            'showitem' => '
                subheader
                --linebreak--,
                header,
            ',
        ],
        'visibility' => [
            'showitem' => '
                hidden;LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:feature_item
            ',
        ],
        // hidden but needs to be included all the time, so sys_language_uid is set correctly
        'hiddenLanguagePalette' => [
            'showitem' => 'sys_language_uid, l10n_parent',
            'isHiddenPalette' => true,
        ],
    ],
    'columns' => [
        'tt_content' => [
            'exclude' => true,
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:feature_item.tt_content',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tt_content',
                'foreign_table_where' => "AND tt_content.pid=###CURRENT_PID### AND tt_content.{#CType}='sitepackage_features'",
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'subheader' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:subheader_formlabel',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'header' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'layout' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:feature_item.layout.small',
                        'value' => 100,
                    ],
                    [
                        'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:feature_item.layout.large',
                        'value' => 200,
                    ],
                ],
                'default' => 100,
            ],
        ],
        'bodytext' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel',
            'l10n_mode' => 'prefixLangTitle',
            'l10n_cat' => 'text',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 15,
                'softref' => 'typolink_tag,email[subst],url',
                'enableRichtext' => true,
            ],
        ],
        'assets' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:asset_references',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-media-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.asset_references.addFileReference',
                ],
                'overrideChildTca' => [
                    'types' => [
                        TYPO3\CMS\Core\Resource\FileType::IMAGE->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        TYPO3\CMS\Core\Resource\FileType::VIDEO->value => [
                            'showitem' => '
                                --palette--;;videoOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        TYPO3\CMS\Core\Resource\FileType::AUDIO->value => [
                            'showitem' => '
                                --palette--;;audioOverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
