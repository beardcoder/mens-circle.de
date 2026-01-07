<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Mens Circle Sitepackage',
    'description' => 'Site configuration and templates for mens-circle.de',
    'category' => 'templates',
    'author' => 'Markus Sommer',
    'author_email' => 'markus@beardcoder.de',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.3.99',
            'fluid_styled_content' => '14.0.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
