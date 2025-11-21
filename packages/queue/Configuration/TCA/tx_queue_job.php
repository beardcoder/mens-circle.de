<?php

declare(strict_types=1);

defined('TYPO3') || exit;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job',
        'label' => 'queue',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'rootLevel' => -1,
        'default_sortby' => 'uid DESC',
        'readOnly' => true,
        'hideTable' => false,
        'searchFields' => 'queue,last_error',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'typeicon_classes' => [
            'default' => 'content-locked',
        ],
    ],
    'columns' => [
        'queue' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.queue',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
                'size' => 30,
            ],
        ],
        'priority' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.priority',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => true,
            ],
        ],
        'attempts' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.attempts',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => true,
            ],
        ],
        'max_attempts' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.max_attempts',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'readOnly' => true,
            ],
        ],
        'available_at' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.available_at',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'reserved_at' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.reserved_at',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'last_error' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.last_error',
            'config' => [
                'type' => 'text',
                'readOnly' => true,
                'enableRichtext' => false,
                'rows' => 4,
            ],
        ],
        'payload' => [
            'label' => 'LLL:EXT:mc_queue/Resources/Private/Language/locallang_db.xlf:tx_queue_job.payload',
            'config' => [
                'type' => 'text',
                'readOnly' => true,
                'rows' => 6,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'queue,priority,attempts,max_attempts,available_at,reserved_at,last_error,payload',
        ],
    ],
];
