<?php

declare(strict_types=1);

use Psr\Log\LogLevel;
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

// Database Configuration
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['charset'] = 'utf8mb4';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] = env('DB_NAME', 'default');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['driver'] = 'mysqli';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] = env('DB_HOST', 'localhost');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['password'] = env('DB_PASSWORD', '');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['port'] = envInt('DB_PORT', 3306);
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['user'] = env('DB_USER', 'root');
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['defaultTableOptions']['charset'] = 'utf8mb4';
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['defaultTableOptions']['collation'] = 'utf8mb4_unicode_ci';

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
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = env('TRUSTED_HOSTS_PATTERN', '.*');

// Frontend Configuration
$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = envInt('FE_DEBUG', 0);
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['enforceValidation'] = true;

// Backend Configuration
$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = envInt('BE_DEBUG', 0);
$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] = envInt('BE_LOCK_SSL', 1);
$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = envInt('BE_SESSION_TIMEOUT', 28800); // 8 hours

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

$redisHost = env('REDIS_HOST', 'redis');
$redisPort = envInt('REDIS_PORT', 6379);
$redisCaches = [
    'pages' => [
        'defaultLifetime' => 86400 * 7, // 1 week
        'compression' => true,
    ],
    'pagesection' => [
        'defaultLifetime' => 86400 * 7,
    ],
    'hash' => [],
    'rootline' => [],
];

$redisDatabase = 0;
foreach ($redisCaches as $name => $values) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['backend']
        = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options'] = [
        'database' => $redisDatabase++,
        'hostname' => $redisHost,
        'port' => $redisPort,
    ];
    if (isset($values['defaultLifetime'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['defaultLifetime']
            = $values['defaultLifetime'];
    }
    if (isset($values['compression'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$name]['options']['compression']
            = $values['compression'];
    }
}


// test to disable all logging for performance reasons
unset($GLOBALS['TYPO3_CONF_VARS']['LOG']);
