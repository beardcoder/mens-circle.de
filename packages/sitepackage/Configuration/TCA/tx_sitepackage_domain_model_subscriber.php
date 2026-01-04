<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Newsletter Subscriber',
        'label' => 'email',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'crdate DESC',
        'searchFields' => 'email',
        'iconfile' => 'EXT:sitepackage/Resources/Public/Icons/subscriber.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'email, status, confirmed_at, unsubscribed_at',
        ],
    ],
    'columns' => [
        'email' => [
            'label' => 'Email',
            'config' => [
                'type' => 'email',
                'size' => 50,
                'required' => true,
            ],
        ],
        'status' => [
            'label' => 'Status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Pending', 'value' => 0],
                    ['label' => 'Confirmed', 'value' => 1],
                    ['label' => 'Unsubscribed', 'value' => 2],
                ],
                'default' => 0,
            ],
        ],
        'token' => [
            'label' => 'Token',
            'config' => [
                'type' => 'input',
                'size' => 64,
                'readOnly' => true,
            ],
        ],
        'confirmed_at' => [
            'label' => 'Confirmed At',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'unsubscribed_at' => [
            'label' => 'Unsubscribed At',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
    ],
];
