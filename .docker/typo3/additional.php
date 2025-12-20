<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Error\DebugExceptionHandler;
use MensCircle\Sitepackage\Error\ProductionExceptionHandler;
use MensCircle\Sitepackage\Log\Writer\SentryLogWriter;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\Backend\ApcuBackend;
use TYPO3\CMS\Core\Cache\Backend\RedisBackend;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Log\Writer\Enum\Interval;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Core\Log\Writer\RotatingFileWriter;

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Optimized boolean environment variable getter.
 */
function envBool(string $key, bool $default = false): bool
{
    $value = env($key);

    if ($value === null || $value === $default) {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '1', 'yes', 'on' => true,
        'false', '0', 'no', 'off', '' => false,
        default => $default,
    };
}

/**
 * Optimized integer environment variable getter.
 */
function envInt(string $key, int $default = 0): int
{
    $value = env($key);
    return $value !== null ? (int) $value : $default;
}

/**
 * Optimized float environment variable getter.
 */
function envFloat(string $key, float $default = 0.0): float
{
    $value = env($key);

    return $value !== null ? (float) $value : $default;
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
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'] = 'pdo_mysql';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] = env('DB_HOST', 'localhost');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] = env('DB_PASSWORD', '');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] = envInt('DB_PORT', 3306);
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] = env('DB_USER', 'root');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['defaultTableOptions']['charset'] = 'utf8mb4';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['defaultTableOptions']['collation'] = 'utf8mb4_unicode_ci';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['options'][PDO::ATTR_PERSISTENT] = envBool('DB_PERSISTENT', false);

// Graphics Configuration
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'ImageMagick';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = '/usr/bin/';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] = '/usr/bin/';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_colorspace'] = 'sRGB';
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
// Explicitly disable warning level logs
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::WARNING][NullWriter::class] = [];
// Keep error level logs for a week
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::ERROR][RotatingFileWriter::class] = [
    'interval' => Interval::DAILY,
    'maxFiles' => envInt('LOG_ERROR_MAX_FILES', 7),
];
// Keep critical level logs for eight weeks
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::CRITICAL][RotatingFileWriter::class] = [
    'interval' => Interval::WEEKLY,
    'maxFiles' => envInt('LOG_CRITICAL_MAX_FILES', 8),
];

// =============================================================================
// Cache Configuration - Optimized for Production with Redis + APCu
// =============================================================================
$redisHost = env('REDIS_HOST', 'redis');
$redisPort = envInt('REDIS_PORT', 6379);
$redisPassword = env('REDIS_PASSWORD', '');
$redisDatabase = envInt('REDIS_DATABASE', 0);

// Redis connection options
$redisDefaultOptions = [
    'hostname' => $redisHost,
    'port' => $redisPort,
    'database' => $redisDatabase,
    'password' => $redisPassword,
    'defaultLifetime' => 86400, // 24 hours default TTL
    'compression' => true,
];

// High-traffic caches -> Redis (persistent, shared across requests)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages'] = [
    'backend' => RedisBackend::class,
    'options' => array_merge($redisDefaultOptions, [
        'database' => $redisDatabase,
        'defaultLifetime' => 86400,
    ]),
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline'] = [
    'backend' => RedisBackend::class,
    'options' => array_merge($redisDefaultOptions, [
        'database' => $redisDatabase + 1,
        'defaultLifetime' => 2592000, // 30 days
    ]),
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['hash'] = [
    'backend' => RedisBackend::class,
    'options' => array_merge($redisDefaultOptions, [
        'database' => $redisDatabase + 2,
        'defaultLifetime' => 2592000,
    ]),
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['imagesizes'] = [
    'backend' => RedisBackend::class,
    'options' => array_merge($redisDefaultOptions, [
        'database' => $redisDatabase + 3,
        'defaultLifetime' => 2592000,
    ]),
];

// Extbase caches -> Redis
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase'] = [
    'backend' => RedisBackend::class,
    'options' => array_merge($redisDefaultOptions, [
        'database' => $redisDatabase + 4,
        'defaultLifetime' => 86400,
    ]),
];

// Fluid template cache -> APCu (very fast, local)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template'] = [
    'backend' => ApcuBackend::class,
    'options' => [
        'defaultLifetime' => 86400,
    ],
];

// Core caches -> APCu (frequently accessed, low latency needed)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['core'] = [
    'backend' => ApcuBackend::class,
    'options' => [
        'defaultLifetime' => 0, // Never expires
    ],
];

// Runtime caches -> TransientMemory (request-scoped, fastest)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['runtime'] = [
    'backend' => TransientMemoryBackend::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['assets'] = [
    'backend' => ApcuBackend::class,
    'options' => [
        'defaultLifetime' => 86400,
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['l10n'] = [
    'backend' => ApcuBackend::class,
    'options' => [
        'defaultLifetime' => 86400,
    ],
];

// DI cache -> APCu (critical for fast bootstrap)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['di'] = [
    'backend' => ApcuBackend::class,
    'options' => [
        'defaultLifetime' => 0,
    ],
];

// Typoscript cache -> Redis
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['typoscript'] = [
    'backend' => RedisBackend::class,
    'options' => array_merge($redisDefaultOptions, [
        'database' => $redisDatabase + 5,
        'defaultLifetime' => 86400,
    ]),
];

