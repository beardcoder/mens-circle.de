<?php

/**
 * Additional TYPO3 Configuration for Production
 * Optimized for Coolify v4 deployment with environment variables
 */

declare(strict_types=1);

use TYPO3\CMS\Core\Cache\Backend\RedisBackend;
use TYPO3\CMS\Core\Core\Environment;

// ============================================
// Database Configuration from Environment
// ============================================

$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = [
    'charset' => 'utf8mb4',
    'driver' => 'mysqli',
    'dbname' => getenv('DB_DATABASE') ?: 'typo3',
    'host' => 'db', // Docker service name
    'password' => getenv('DB_PASSWORD') ?: '',
    'port' => 3306,
    'user' => getenv('DB_USERNAME') ?: 'typo3',
    'tableoptions' => [
        'charset' => 'utf8mb4',
        'collate' => 'utf8mb4_unicode_ci',
    ],
];

// ============================================
// Redis Cache Backend (Production)
// ============================================

// Configure Redis for all caches (using Docker service name)
$cacheConfigurations = [
    'pages',
    'pagesection',
    'hash',
    'rootline',
    'extbase',
    'assets',
];

foreach ($cacheConfigurations as $cacheName) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['backend'] = RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['options'] = [
        'hostname' => 'redis', // Docker service name
        'port' => 6379,
        'database' => 0,
        'compression' => true,
    ];
}

// ============================================
// Mail Configuration
// ============================================

$mailTransport = getenv('TYPO3_MAIL_TRANSPORT');
if ($mailTransport === 'smtp') {
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = getenv('TYPO3_MAIL_SMTP_SERVER') ?: 'localhost:25';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = getenv('TYPO3_MAIL_SMTP_ENCRYPT') ?: '';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = getenv('TYPO3_MAIL_SMTP_USERNAME') ?: '';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = getenv('TYPO3_MAIL_SMTP_PASSWORD') ?: '';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = getenv('TYPO3_MAIL_DEFAULT_FROM') ?: 'noreply@example.com';
    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = getenv('TYPO3_MAIL_DEFAULT_FROM_NAME') ?: 'TYPO3';
}

// ============================================
// Security Settings
// ============================================

// Encryption Key
$encryptionKey = getenv('TYPO3_ENCRYPTION_KEY');
if ($encryptionKey) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $encryptionKey;
}

// Install Tool Password
$installToolPassword = getenv('INSTALL_TOOL_PASSWORD');
if ($installToolPassword) {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] = $installToolPassword;
}

// ============================================
// Production Optimizations
// ============================================

// Disable debugging
$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 0;

// Enable all caches
$GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] = true;
$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = true;

// Optimize Backend
$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = 9;
$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] = 28800; // 8 hours

// Security Headers
$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] = true;
$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort'] = 443;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = getenv('SERVER_NAME') ?: '.*';

// ============================================
// Image Processing
// ============================================

$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] = 'ImageMagick';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = '/usr/bin/';
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'] = true;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_effects'] = true;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] = false;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_colorspace'] = 'RGB';

// WebP/AVIF Support (TYPO3 v13)
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_webp'] = true;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_avif'] = true;

// ============================================
// Logging
// ============================================

// Log to stdout for Docker/Coolify
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = [
    \Psr\Log\LogLevel::WARNING => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFileInfix' => 'error',
        ],
    ],
];

// ============================================
// Sentry Error Tracking (optional)
// ============================================

$sentryDsn = getenv('SENTRY_DSN');
if ($sentryDsn) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'] = \Networkteam\SentryClient\ErrorHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_USER_DEPRECATED);

    if (class_exists(\Sentry\ClientBuilder::class)) {
        \Sentry\init([
            'dsn' => $sentryDsn,
            'environment' => getenv('SENTRY_ENVIRONMENT') ?: 'production',
            'release' => getenv('SENTRY_RELEASE') ?: 'unknown',
            'traces_sample_rate' => 0.1,
        ]);
    }
}

// ============================================
// FrankenPHP Worker Mode (optional)
// ============================================

if (getenv('FRANKENPHP_CONFIG')) {
    // Enable output buffering for worker mode
    $GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'] = 0;
}
