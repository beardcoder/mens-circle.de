<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "sitepackage" by Markus Sommer.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\Backend\RedisBackend;
use TYPO3\CMS\Core\Log\Writer\Enum\Interval;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Log\Writer\RotatingFileWriter;
use MensCircle\Sitepackage\Error\DebugExceptionHandler;
use MensCircle\Sitepackage\Error\ProductionExceptionHandler;
use MensCircle\Sitepackage\Log\Writer\SentryLogWriter;

/**
 * Helper function to get environment variable with fallback
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Helper function to get boolean environment variable
 */
function envBool(string $key, bool $default = false): bool
{
    $value = env($key);
    if ($value === null) {
        return $default;
    }
    return match (strtolower((string)$value)) {
        'true', '1', 'yes', 'on' => true,
        'false', '0', 'no', 'off', '' => false,
        default => $default,
    };
}

/**
 * Helper function to get integer environment variable
 */
function envInt(string $key, int $default = 0): int
{
    $value = env($key);
    return $value !== null ? (int)$value : $default;
}

/**
 * Helper function to get float environment variable
 */
function envFloat(string $key, float $default = 0.0): float
{
    $value = env($key);
    return $value !== null ? (float)$value : $default;
}

// Exception Handlers
$GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = ProductionExceptionHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = DebugExceptionHandler::class;

// Sentry Logging Configuration
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::ERROR][SentryLogWriter::class] = [
    'addBreadcrumbs' => true,
];

// Database Configuration
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['charset'] = 'utf8mb4';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] = env('DB_NAME', 'default');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['defaultTableOptions'] = [
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'] = 'pdo_mysql';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] = env('DB_HOST', 'localhost');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] = env('DB_PASSWORD', '');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] = envInt('DB_PORT', 3306);
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] = env('DB_USER', 'root');
// TYPO3 v14 specific: Enable persistent connections for FrankenPHP
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['options'][PDO::ATTR_PERSISTENT] = envBool('DB_PERSISTENT', false);

// Graphics Configuration
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'ImageMagick';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = '/usr/bin/';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] = '/usr/bin/';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_colorspace'] = 'sRGB';
// TYPO3 v14: Optimize image processing
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileCommand'] = '+profile \'*\'';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileParameters'] = '';

// Mail Configuration
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = env('MAIL_TRANSPORT', 'smtp');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_sendmail_command'] = '';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = envBool('MAIL_SMTP_ENCRYPT', true);
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = env('MAIL_SMTP_PASSWORD', '');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = env('MAIL_SMTP_SERVER', 'localhost:587');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = env('MAIL_SMTP_USERNAME', '');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_domain'] = env('MAIL_SMTP_DOMAIN', '');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = env('MAIL_FROM_ADDRESS', 'noreply@example.com');
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = env('MAIL_FROM_NAME', 'Mens Circle Niederbayern');

// System Configuration
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = env('DEV_IP_MASK', '');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = envInt('DISPLAY_ERRORS', 0);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'] = envInt('SQL_DEBUG', 0);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyHeaderMultiValue'] = 'first';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP'] = '*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxySSL'] = '*';
// TYPO3 v14: Optimize for FrankenPHP/Caddy
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = env('TRUSTED_HOSTS_PATTERN', '.*');

// Runtime Cache Configuration (APCu or TransientMemory)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['runtime']['backend'] = envBool('APCU_ENABLED', false)
    ? \TYPO3\CMS\Core\Cache\Backend\ApcuBackend::class
    : \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class;

// Frontend Configuration
$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = envInt('FE_DEBUG', 0);
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation'] = true;

// Backend Configuration
$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = envInt('BE_DEBUG', 0);
$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] = envInt('BE_LOCK_SSL', 1);
$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = envInt('BE_SESSION_TIMEOUT', 28800); // 8 hours

// HTTP Client Configuration
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'] = envInt('HTTP_TIMEOUT', 30);
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['connect_timeout'] = envInt('HTTP_CONNECT_TIMEOUT', 10);

// Sentry Configuration
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry_client']['options']['traces_sample_rate'] = envFloat('SENTRY_TRACES_SAMPLE_RATE', 1.0);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry_client']['options']['environment'] = env('APP_ENV', 'production');

// Log Writer Configuration
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
    LogLevel::WARNING => [
        // Explicitly disable warning level logs
        NullWriter::class => [],
    ],
    LogLevel::ERROR => [
        // Keep error level logs for a week
        RotatingFileWriter::class => [
            'interval' => Interval::DAILY,
            'maxFiles' => envInt('LOG_ERROR_MAX_FILES', 7),
        ],
    ],
    LogLevel::CRITICAL => [
        // Keep critical level logs for eight weeks
        RotatingFileWriter::class => [
            'interval' => Interval::WEEKLY,
            'maxFiles' => envInt('LOG_CRITICAL_MAX_FILES', 8),
        ],
    ],
];

// Redis Cache Configuration
$redisEnabled = envBool('REDIS_ENABLED', true);

if ($redisEnabled) {
    $redisHost = env('REDIS_HOST', 'localhost');
    $redisPort = envInt('REDIS_PORT', 6379);
    $redisPassword = env('REDIS_PASSWORD', '');

    $redisCaches = [
        'pages' => [
            'defaultLifetime' => envInt('REDIS_CACHE_PAGES_LIFETIME', 604800), // 1 week
            'compression' => envBool('REDIS_CACHE_PAGES_COMPRESSION', true),
        ],
        'pagesection' => [
            'defaultLifetime' => envInt('REDIS_CACHE_PAGESECTION_LIFETIME', 604800),
        ],
        'hash' => [
            'defaultLifetime' => envInt('REDIS_CACHE_HASH_LIFETIME', 0), // Unlimited
        ],
        'rootline' => [
            'defaultLifetime' => envInt('REDIS_CACHE_ROOTLINE_LIFETIME', 0), // Unlimited
        ],
    ];

    $redisDatabase = 0;
    foreach ($redisCaches as $name => $values) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name] ??= [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['backend'] = RedisBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options'] = [
            'database' => $redisDatabase++,
            'hostname' => $redisHost,
            'port' => $redisPort,
            'password' => $redisPassword,
            // TYPO3 v14: Enable persistent Redis connections for FrankenPHP
            'persistentConnection' => envBool('REDIS_PERSISTENT', true),
            'connectionTimeout' => envFloat('REDIS_TIMEOUT', 2.5),
            'compressionLevel' => envInt('REDIS_COMPRESSION_LEVEL', 1),
        ];

        if (isset($values['defaultLifetime'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['defaultLifetime'] = $values['defaultLifetime'];
        }

        if (isset($values['compression'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['compression'] = $values['compression'];
        }
    }
}

// FrankenPHP Specific Optimizations
if (PHP_SAPI === 'frankenphp' || envBool('FRANKENPHP_ENABLED', false)) {
    // Enable persistent connections and optimize for long-running process
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['runtime'] = [
        'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
        'options' => [],
    ];

    // Optimize session handling for FrankenPHP
    $GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime'] = envInt('FE_SESSION_LIFETIME', 86400);
    $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = envInt('BE_SESSION_TIMEOUT', 28800);
}

// Coolify Specific: Health check endpoint optimization
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = env('PAGE_NOT_FOUND_HANDLING', '');
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = env('PAGE_UNAVAILABLE_HANDLING', '');
