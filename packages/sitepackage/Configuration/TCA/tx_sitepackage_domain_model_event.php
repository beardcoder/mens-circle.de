<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Enum\EventAttendanceModeEnum;
use MensCircle\Sitepackage\Service\EventSlugService;

use function Symfony\Component\Clock\now;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event',
        'label' => 'title',
        'label_alt' => 'start_date',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'start_date',
        'typeicon_classes' => [
            'default' => 'tx-sitepackage-domain-model-event',
        ],
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],
    'types' => [
        1 => [
            'showitem' => implode(',', [
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general',
                'title, description, attendance_mode, image, call_url, slug, cancelled,--palette--;;date, location',
                '--div--;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.registrations',
                'registration',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access',
                '--palette--;;hidden',
            ]),
        ],
    ],
    'palettes' => [
        'address' => [
            'showitem' => 'place, --linebreak--, address, --linebreak--, city, zip, --linebreak--, longitude, latitude',
        ],
        'date' => [
            'showitem' => 'start_date, end_date',
        ],
        'hidden' => [
            'showitem' => 'hidden;LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden',
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
                'searchable' => true,
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.description',
            'config' => [
                'type' => 'text',
                'rows' => 2,
                'cols' => 50,
                'searchable' => false,
            ],
        ],
        'image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
            ],
        ],
        'slug' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.slug',
            'displayCond' => 'VERSION:IS:false',
            'exclude' => true,
            'config' => [
                'type' => 'slug',
                'eval' => 'unique',
                'size' => 50,
                'appearance' => [
                    'prefix' => EventSlugService::class.'->getPrefix',
                ],
                'generatorOptions' => [
                    'fields' => ['title', 'start_date'],
                    'replacements' => [
                        '/' => '-',
                    ],
                    'postModifiers' => [EventSlugService::class.'->modifySlug'],
                ],
                'fallbackCharacter' => '-',
                'default' => '',
                'searchable' => false,
            ],
        ],
        'call_url' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.call_url.label',
            'displayCond' => 'FIELD:attendance_mode:=:1',
            'exclude' => true,
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'searchable' => false,
            ],
        ],
        'start_date' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.startdate',
            'exclude' => true,
            'config' => [
                'type' => 'datetime',
                'default' => now()
                    ->getTimestamp(),
                'required' => true,
                'searchable' => true,
            ],
        ],
        'end_date' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.enddate',
            'exclude' => true,
            'config' => [
                'type' => 'datetime',
                'default' => now()
                    ->getTimestamp(),
                'required' => true,
                'searchable' => false,
            ],
        ],
        'cancelled' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.cancelled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    0 => [
                        'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.cancelled',
                    ],
                ],
            ],
        ],
        'location' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location',
            'description' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_location.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_sitepackage_domain_model_location',
                'maxitems' => 1,
            ],
        ],
        'registration' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.registrations',
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_sitepackage_domain_model_participant',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => 1,
                ],
            ],
        ],
        'notification' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.notification',
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_sitepackage_domain_model_eventnotification',
                'foreign_field' => 'event',
                'maxitems' => 9999,
                'appearance' => [
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => 1,
                ],
            ],
        ],
        'attendance_mode' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_event.attendance_mode.label',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => EventAttendanceModeEnum::selects(),
            ],
        ],
    ],
];
