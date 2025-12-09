<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

return [
    'dependencies' => ['core', 'backend', 'rte_ckeditor'],
    'imports' => [
        '@mens-circle/sitepackage/' => [
            'path' => 'EXT:sitepackage/Resources/Public/Backend/Scripts/',
        ],
    ],
];
