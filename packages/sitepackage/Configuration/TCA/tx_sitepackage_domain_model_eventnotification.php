<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_eventnotification',
        'label' => 'subject',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'typeicon_classes' => [
            'default' => 'tx-sitepackage-domain-model-eventnotification',
        ],
    ],
    'types' => [
        1 => [
            'showitem' => '--div--;core.form.tabs:general,subject, message, event,--div--;LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_eventnotification.tabs.registration,registration',
        ],
    ],
    'columns' => [
        'subject' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_eventnotification.subject',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
                'searchable' => true,
            ],
        ],
        'message' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_eventnotification.message',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'searchable' => false,
            ],
        ],
        'event' => [
            'config' => [
                'type' => 'passthrough',
                'foreign_table' => 'tx_sitepackage_domain_model_event',
            ],
        ],
    ],
];
