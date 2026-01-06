<?php

declare(strict_types=1);

/**
 * TYPO3 v14 Additional Configuration
 * Handles both Development (DDEV) and Production (Docker/Coolify) environments
 */

// Load helper class (required before autoloader is available)
require_once __DIR__ . '/../../packages/sitepackage/Classes/Utility/Helpers.php';

use MensCircle\Sitepackage\Utility\Helpers;

// Alias for shorter access
$env = Helpers::env(...);
$isProduction = Helpers::isProduction();

// =============================================================================
// Database Configuration (from environment)
// =============================================================================
$dbConfig = Helpers::databaseConfig();
if (!empty($dbConfig['host'])) {
    $dbDriver = $env('DB_DRIVER', $env('TYPO3_DB_DRIVER', 'pdo_mysql'));
    $dbPort = $dbConfig['port'];
    if ($dbPort === null || $dbPort === '') {
        $dbPort = str_contains($dbDriver, 'pgsql') ? 5432 : 3306;
    }

    // Base configuration
    $dbConnection = [
        'driver' => $dbDriver,
        'host' => $dbConfig['host'],
        'port' => (int) $dbPort,
        'dbname' => $dbConfig['database'] ?? 'typo3',
        'user' => $dbConfig['username'] ?? 'typo3',
        'password' => $dbConfig['password'] ?? '',
    ];

    // Driver-specific settings
    if (str_contains($dbDriver, 'pgsql')) {
        // PostgreSQL
        $dbConnection['charset'] = 'UTF8';
    } else {
        // MySQL/MariaDB
        $dbConnection['charset'] = 'utf8mb4';
        $dbConnection['tableoptions'] = [
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
        ];
    }

    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = $dbConnection;
}

// =============================================================================
// Mail Configuration (SMTP from environment)
// =============================================================================
$mailConfig = Helpers::mailConfig();
if (!empty($mailConfig['host'])) {
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = $mailConfig['transport'];
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = sprintf(
        '%s:%s',
        $mailConfig['host'],
        $mailConfig['port']
    );
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = $mailConfig['encryption'];
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = $mailConfig['username'];
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = $mailConfig['password'];
}

$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = $mailConfig['from_address'];
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = $mailConfig['from_name'];
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = $env('MAIL_REPLY_TO', '');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToName'] = $env('MAIL_REPLY_TO_NAME', '');

// =============================================================================
// Encryption Key (from environment)
// =============================================================================
$encryptionKey = $env('TYPO3_ENCRYPTION_KEY', '');
if (!empty($encryptionKey)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $encryptionKey;
}

// =============================================================================
// Install Tool Password (from environment)
// =============================================================================
$installToolPassword = $env('TYPO3_INSTALL_TOOL_PASSWORD', '');
if (!empty($installToolPassword)) {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = $installToolPassword;
}

// =============================================================================
// Redis Caching (optional, from environment)
// =============================================================================
$redisConfig = Helpers::redisConfig();
if ($redisConfig !== null) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['hash']['backend'] =
        \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['hash']['options'] = [
        'hostname' => $redisConfig['host'],
        'port' => $redisConfig['port'],
        'password' => $redisConfig['password'],
        'database' => $redisConfig['database'],
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend'] =
        \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options'] = [
        'hostname' => $redisConfig['host'],
        'port' => $redisConfig['port'],
        'password' => $redisConfig['password'],
        'database' => $redisConfig['database'] + 1,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['backend'] =
        \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['options'] = [
        'hostname' => $redisConfig['host'],
        'port' => $redisConfig['port'],
        'password' => $redisConfig['password'],
        'database' => $redisConfig['database'] + 2,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE']['backend'] =
        \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE']['options'] = [
        'hostname' => $redisConfig['host'],
        'port' => $redisConfig['port'],
        'password' => $redisConfig['password'],
        'database' => $redisConfig['database'] + 3,
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['FE']['backend'] =
        \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['FE']['options'] = [
        'hostname' => $redisConfig['host'],
        'port' => $redisConfig['port'],
        'password' => $redisConfig['password'],
        'database' => $redisConfig['database'] + 4,
    ];
}

// =============================================================================
// Production Settings
// =============================================================================
if ($isProduction) {
    // Disable debugging
    $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 0;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['belogErrorReporting'] = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE;

    // Security
    $GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] = Helpers::forcesSsl();
    $GLOBALS['TYPO3_CONF_VARS']['FE']['lockSSL'] = Helpers::forcesSsl();
    $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['className'] = \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash::class;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['className'] = \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = true;

    // Reverse Proxy (Coolify)
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] = $env('TRUSTED_PROXIES', '*');
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue'] = 'first';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxySSL'] = '*';

    // Trusted hosts - allow main domain and all subdomains
    $trustedHosts = $env('TRUSTED_HOSTS', '');
    if (!empty($trustedHosts)) {
        // Use custom pattern from environment
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = $trustedHosts;
    } else {
        $appHost = Helpers::appHost();
        if (!empty($appHost) && $appHost !== 'localhost') {
            // Pattern: match domain and all subdomains (e.g., mens-circle.de, staging.mens-circle.de, www.mens-circle.de)
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = sprintf(
                '^(.+\.)?%s$',
                preg_quote($appHost, '/')
            );
        }
    }

    // Performance
    $GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'] = 0;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation'] = true;

    // Backend settings
    $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] = $env('ADMIN_EMAIL', '');
    $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode'] = 2;
    $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = 28800;

    // Logging
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
        \Psr\Log\LogLevel::WARNING => [
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                'logFileInfix' => 'warning',
            ],
        ],
        \Psr\Log\LogLevel::ERROR => [
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                'logFileInfix' => 'error',
            ],
        ],
    ];
}

// =============================================================================
// Development Settings
// =============================================================================
if (!$isProduction) {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL;

    // Disable caching for development (unless Redis is configured)
    if (!Helpers::hasRedis()) {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [] as $key => $_) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$key]['backend'] =
                \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
        }
    }
}

// =============================================================================
// Image Processing
// =============================================================================
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'ImageMagick';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = '/usr/bin/';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_colorspace'] = 'sRGB';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai,svg,webp,avif';

// =============================================================================
// System Settings
// =============================================================================
$GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'] = true;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] = 'de_DE.UTF-8';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] = 'txt,ts,typoscript,html,htm,css,js,sql,xml,csv,xlf,yaml,yml,json,md';
