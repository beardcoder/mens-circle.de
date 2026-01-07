<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Utility;

use TYPO3\CMS\Core\Core\Environment;

/**
 * Helper functions for TYPO3 v14
 * Inspired by Laravel/Symfony helper patterns
 */
final class Helpers
{
    /**
     * Get environment variable with type casting and default value
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => self::castValue($value),
        };
    }

    /**
     * Cast string value to appropriate type
     */
    private static function castValue(string $value): mixed
    {
        // Check for quoted strings
        if (preg_match('/^"(.+)"$/', $value, $matches) ||
            preg_match("/^'(.+)'$/", $value, $matches)) {
            return $matches[1];
        }

        // Check for numeric values
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * Get the first defined environment variable value from a list of keys
     */
    private static function firstEnvValue(array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = getenv($key);
            if ($value !== false) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Check if application is in production mode
     */
    public static function isProduction(): bool
    {
        $context = self::env('TYPO3_CONTEXT', 'Development');
        return str_starts_with((string) $context, 'Production');
    }

    /**
     * Check if application is in development mode
     */
    public static function isDevelopment(): bool
    {
        return !self::isProduction();
    }

    /**
     * Get the application URL
     */
    public static function appUrl(): string
    {
        return (string) self::env('APP_URL', 'http://localhost');
    }

    /**
     * Get the application host
     */
    public static function appHost(): string
    {
        $url = self::appUrl();
        return parse_url($url, PHP_URL_HOST) ?: 'localhost';
    }

    /**
     * Check if SSL is forced
     */
    public static function forcesSsl(): bool
    {
        return (bool) self::env('FORCE_SSL', self::isProduction());
    }

    /**
     * Get database configuration array
     */
    public static function databaseConfig(): array
    {
        $map = [
            'host' => ['DB_HOST', 'TYPO3_DB_HOST'],
            'port' => ['DB_PORT', 'TYPO3_DB_PORT'],
            'database' => ['DB_DATABASE', 'TYPO3_DB_NAME'],
            'username' => ['DB_USERNAME', 'TYPO3_DB_USER'],
            'password' => ['DB_PASSWORD', 'TYPO3_DB_PASSWORD'],
        ];

        $config = [];
        $hasConfig = false;

        foreach ($map as $key => $keys) {
            $value = self::firstEnvValue($keys);
            if ($value !== null) {
                $hasConfig = true;
            }
            $config[$key] = $value;
        }

        if (!$hasConfig) {
            return [];
        }

        return [
            'host' => $config['host'] ?? 'localhost',
            'port' => $config['port'],
            'database' => $config['database'] ?? 'typo3',
            'username' => $config['username'] ?? 'typo3',
            'password' => $config['password'] ?? '',
        ];
    }

    /**
     * Get mail configuration array
     */
    public static function mailConfig(): array
    {
        return [
            'transport' => self::env('MAIL_TRANSPORT', 'smtp'),
            'host' => self::env('MAIL_HOST', 'localhost'),
            'port' => self::env('MAIL_PORT', 587),
            'encryption' => self::env('MAIL_ENCRYPTION', 'tls'),
            'username' => self::env('MAIL_USERNAME', ''),
            'password' => self::env('MAIL_PASSWORD', ''),
            'from_address' => self::env('MAIL_FROM_ADDRESS', 'noreply@mens-circle.de'),
            'from_name' => self::env('MAIL_FROM_NAME', 'Mens Circle'),
        ];
    }

    /**
     * Get Redis configuration array
     */
    public static function redisConfig(): ?array
    {
        $host = self::env('REDIS_HOST', '');

        if (empty($host)) {
            return null;
        }

        return [
            'host' => $host,
            'port' => self::env('REDIS_PORT', 6379),
            'password' => self::env('REDIS_PASSWORD', ''),
            'database' => self::env('REDIS_DATABASE', 0),
        ];
    }

    /**
     * Check if Redis is configured
     */
    public static function hasRedis(): bool
    {
        return !empty(self::env('REDIS_HOST', ''));
    }

    /**
     * Generate a secure random string
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a secure token
     */
    public static function generateToken(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Hash a value using TYPO3's encryption key
     */
    public static function hash(string $value): string
    {
        $key = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? '';
        return hash_hmac('sha256', $value, (string) $key);
    }

    /**
     * Get absolute path to a resource
     */
    public static function resourcePath(string $path = ''): string
    {
        $basePath = Environment::getPublicPath();
        return $basePath . ($path !== '' && $path !== '0' ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get absolute path to var directory
     */
    public static function varPath(string $path = ''): string
    {
        $basePath = Environment::getVarPath();
        return $basePath . ($path !== '' && $path !== '0' ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Format bytes to human readable string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Check if current request is AJAX
     */
    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /**
     * Get client IP address (respects reverse proxy)
     */
    public static function clientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', (string) $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        return '127.0.0.1';
    }

    /**
     * Dump variable and die (development only)
     */
    public static function dd(mixed ...$vars): never
    {
        if (self::isProduction()) {
            exit(1);
        }

        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }

        exit(1);
    }

    /**
     * Log to file (simple logging)
     */
    public static function log(string $message, string $level = 'info', string $channel = 'app'): void
    {
        $logPath = self::varPath('log');
        $file = sprintf('%s/%s.log', $logPath, $channel);
        $timestamp = date('Y-m-d H:i:s');
        $line = sprintf("[%s] %s: %s\n", $timestamp, strtoupper($level), $message);

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
