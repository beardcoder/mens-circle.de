<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

return [
    'ctrl' => [
        'title' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_newsletter',
        'label' => 'subject',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'typeicon_classes' => [
            'default' => 'tx-sitepackage-domain-model-newsletter',
        ],
    ],
    'types' => [
        1 => [
            'showitem' => '--div--;core.form.tabs:general,subject, message, subscriptions',
        ],
    ],
    'columns' => [
        'subject' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_newsletter.subject',
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
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_newsletter.message',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'searchable' => false,
            ],
        ],
        'subscriptions' => [
            'label' => 'LLL:EXT:sitepackage/Resources/Private/Language/locallang_db.xlf:tx_sitepackage_domain_model_newsletter.subscriptions',
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_sitepackage_domain_model_subscription',
                'MM' => 'tx_sitepackage_domain_model_subscription_rel',
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
            ],
        ],
    ],
];
