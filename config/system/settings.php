<?php

use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;

return [
    'BE' => [
        'debug' => false,
        'installToolPassword' => '$argon2id$v=19$m=65536,t=16,p=1$placeholder',
        'passwordHashing' => [
            'className' => Argon2idPasswordHash::class,
            'options' => [],
        ],
    ],
    'DB' => [
        'Connections' => [
            'Default' => [
                'charset' => 'utf8mb4',
                'dbname' => 'db',
                'driver' => 'mysqli',
                'host' => 'db',
                'password' => 'db',
                'port' => 3306,
                'defaultTableOptions' => [
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
                'user' => 'db',
            ],
        ],
    ],
    'EXTENSIONS' => [
        'backend' => [
            'backendFavicon' => '',
            'backendLogo' => '',
            'loginBackgroundImage' => '',
            'loginFootnote' => '',
            'loginHighlightColor' => '',
            'loginLogo' => '',
            'loginLogoAlt' => '',
        ],
        'extensionmanager' => [
            'automaticInstallation' => '1',
            'offlineMode' => '0',
        ],
        'scheduler' => [
            'maxLifetime' => '1440',
        ],
        'vite_asset_collector' => [
            'defaultManifest' => '_assets/vite/.vite/manifest.json',
            'devServerUri' => 'auto',
            'useDevServer' => 'auto',
        ],
    ],
    'FE' => [
        'cacheHash' => [
            'enforceValidation' => true,
        ],
        'debug' => false,
        'disableNoCacheParameter' => true,
        'passwordHashing' => [
            'className' => Argon2idPasswordHash::class,
            'options' => [],
        ],
    ],
    'GFX' => [
        'processor' => 'ImageMagick',
        'processor_path' => '/usr/bin/',
    ],
    'LOG' => [
        'writerConfiguration' => [
            'warning' => [
                FileWriter::class => [
                    'logFileInfix' => 'main',
                ],
            ],
        ],
    ],
    'MAIL' => [
        'defaultMailFromAddress' => 'noreply@mens-circle.de',
        'defaultMailFromName' => 'Mens Circle',
        'transport' => 'smtp',
        'transport_smtp_encrypt' => false,
        'transport_smtp_server' => 'localhost:1025',
    ],
    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'hash' => [
                    'backend' => Typo3DatabaseBackend::class,
                ],
                'pages' => [
                    'backend' => Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                    ],
                ],
                'rootline' => [
                    'backend' => Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                    ],
                ],
            ],
        ],
        'devIPmask' => '',
        'displayErrors' => 0,
        'encryptionKey' => 'placeholder-encryption-key-replace-in-production',
        'exceptionalErrors' => 6135,
        'sitename' => 'Mens Circle',
        'trustedHostsPattern' => '.*\\.ddev\\.site$|^mens-circle\\.de$|^www\\.mens-circle\\.de$',
    ],
];
