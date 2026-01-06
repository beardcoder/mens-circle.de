<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Newsletter',
        'label' => 'subject',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'crdate DESC',
        'searchFields' => 'subject, content',
        'iconfile' => 'EXT:sitepackage/Resources/Public/Icons/newsletter.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;General,
                    subject, preheader, content,
                --div--;Delivery,
                    status, scheduled_at, sent_at,
                --div--;Statistics,
                    recipients_count, sent_count, failed_count,
            ',
        ],
    ],
    'columns' => [
        'subject' => [
            'label' => 'Subject',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
            ],
        ],
        'preheader' => [
            'label' => 'Preheader',
            'description' => 'Preview text shown in email clients',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'content' => [
            'label' => 'Content',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'rows' => 15,
            ],
        ],
        'status' => [
            'label' => 'Status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Draft', 'value' => 0],
                    ['label' => 'Scheduled', 'value' => 1],
                    ['label' => 'Sending', 'value' => 2],
                    ['label' => 'Sent', 'value' => 3],
                    ['label' => 'Failed', 'value' => 4],
                ],
                'default' => 0,
            ],
        ],
        'scheduled_at' => [
            'label' => 'Scheduled At',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
            ],
        ],
        'sent_at' => [
            'label' => 'Sent At',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'recipients_count' => [
            'label' => 'Total Recipients',
            'config' => [
                'type' => 'number',
                'readOnly' => true,
            ],
        ],
        'sent_count' => [
            'label' => 'Sent Successfully',
            'config' => [
                'type' => 'number',
                'readOnly' => true,
            ],
        ],
        'failed_count' => [
            'label' => 'Failed',
            'config' => [
                'type' => 'number',
                'readOnly' => true,
            ],
        ],
    ],
];
