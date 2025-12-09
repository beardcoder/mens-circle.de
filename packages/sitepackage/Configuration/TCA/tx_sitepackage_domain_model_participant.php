<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

return [
    'ctrl' => [
        'title' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_participant',
        'label' => 'first_name',
        'label_alt' => 'last_name',
        'label_alt_force' => true,
        'default_sortby' => 'first_name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'typeicon_classes' => [
            'default' => 'tx-sitepackage-domain-model-participant',
        ],
    ],
    'types' => [
        1 => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                'first_name, last_name, email, fe_user',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access',
                '--palette--;;hidden',
            ]),
        ],
    ],
    'palettes' => [
        'hidden' => [
            'showitem' => 'hidden;LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden',
        ],
    ],
    'columns' => [
        'event' => [
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tx_sitepackage_domain_model_event',
            ],
        ],
        'first_name' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_participant.first_name',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
                'searchable' => true,
            ],
        ],
        'last_name' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_participant.last_name',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
                'searchable' => false,
            ],
        ],
        'email' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_participant.email',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
                'searchable' => false,
            ],
        ],
        'fe_user' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:user',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'minitems' => 0,
                'items' => [
                    [
                        'label' => null,
                        'value' => '',
                    ],
                ],
                'default' => null,
            ],
        ],
    ],
];
