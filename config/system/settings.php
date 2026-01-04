<?php

declare(strict_types=1);

return [
    'BE' => [
        'debug' => false,
        'installToolPassword' => '$argon2id$v=19$m=65536,t=16,p=1$placeholder',
        'passwordHashing' => [
            'className' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash::class,
            'options' => [],
        ],
    ],
    'DB' => [
        'Connections' => [
            'Default' => [
                'charset' => 'utf8mb4',
                'driver' => 'mysqli',
                'dbname' => getenv('TYPO3_DB_NAME') ?: 'db',
                'host' => getenv('TYPO3_DB_HOST') ?: 'db',
                'password' => getenv('TYPO3_DB_PASSWORD') ?: 'db',
                'port' => (int)(getenv('TYPO3_DB_PORT') ?: 3306),
                'user' => getenv('TYPO3_DB_USER') ?: 'db',
                'tableoptions' => [
                    'charset' => 'utf8mb4',
                    'collate' => 'utf8mb4_unicode_ci',
                ],
            ],
        ],
    ],
    'FE' => [
        'cacheHash' => [
            'enforceValidation' => true,
        ],
        'debug' => false,
        'disableNoCacheParameter' => true,
        'passwordHashing' => [
            'className' => \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash::class,
            'options' => [],
        ],
    ],
    'GFX' => [
        'processor' => 'ImageMagick',
        'processor_path' => '/usr/bin/',
        'processor_colorspace' => 'sRGB',
    ],
    'LOG' => [
        'writerConfiguration' => [
            \Psr\Log\LogLevel::WARNING => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                    'logFileInfix' => 'main',
                ],
            ],
        ],
    ],
    'MAIL' => [
        'transport' => 'smtp',
        'transport_smtp_server' => getenv('TYPO3_MAIL_SMTP_SERVER') ?: 'localhost:1025',
        'transport_smtp_encrypt' => false,
        'defaultMailFromAddress' => 'noreply@mens-circle.de',
        'defaultMailFromName' => 'Mens Circle',
    ],
    'SYS' => [
        'caching' => [
            'cacheConfigurations' => [
                'hash' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                ],
                'pages' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                    ],
                ],
                'rootline' => [
                    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
                    'options' => [
                        'compression' => true,
                    ],
                ],
            ],
        ],
        'devIPmask' => '',
        'displayErrors' => 0,
        'encryptionKey' => getenv('TYPO3_ENCRYPTION_KEY') ?: 'placeholder-encryption-key-replace-in-production',
        'exceptionalErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED | E_USER_DEPRECATED),
        'sitename' => 'Mens Circle',
        'trustedHostsPattern' => '.*\\.ddev\\.site$|^mens-circle\\.de$|^www\\.mens-circle\\.de$',
    ],
];
