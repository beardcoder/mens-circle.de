<?php

use MensCircle\Sitepackage\Enum\EventAttendanceModeEnum;
use MensCircle\Sitepackage\Service\EventSlugService;

use function Symfony\Component\Clock\now;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location',
        'label' => 'place',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'place',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_category',
        ],
        'searchFields' => 'place',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],
    'types' => [
        1 => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                'place, --palette--;;address, --palette--;;latlong',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access',
                '--palette--;;hidden',

            ]),
        ],
    ],
    'palettes' => [
        'address' => [
            'showitem' => 'address, --linebreak--, city, zip',
        ],
        'latlong' => [
            'showitem' => 'longitude, latitude',
        ],
        'hidden' => [
            'showitem' => 'hidden;LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden',
        ],
    ],
    'columns' => [
        'place' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location.place',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'address' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location.address',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'zip' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location.zip',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 4,
            ],
        ],
        'city' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location.city',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'longitude' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location.longitude',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 11,
                'max' => 11,
                'default' => '0.00',
                'eval' => 'trim',
            ],
        ],
        'latitude' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location.latitude',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 11,
                'max' => 11,
                'default' => '0.00',
                'eval' => 'trim',
            ],
        ],
    ],
];
