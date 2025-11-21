<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Database Queue',
    'description' => 'Database-backed queue similar to Laravel queues.',
    'category' => 'services',
    'version' => '0.1.0',
    'state' => 'beta',
    'author' => 'Mens Circle',
    'author_email' => 'hallo@mens-circle.de',
    'constraints' => [
        'depends' => [
            'typo3' => '13.2.0-13.9.99',
        ],
    ],
];
