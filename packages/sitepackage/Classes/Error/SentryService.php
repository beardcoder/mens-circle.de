<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Error;

use Sentry\Event;
use Sentry\EventHint;
use Sentry\EventId;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function Sentry\captureException;
use function Sentry\init;
use function Sentry\withScope;

/**
 * Sentry integration service for TYPO3 v14
 *
 * Supports:
 * - Error tracking with context
 * - Performance monitoring (Tracing)
 * - Profiling (requires Excimer extension)
 * - Spotlight for local development
 * - Metrics
 * - Breadcrumbs
 */
final class SentryService implements SingletonInterface
{
    private static bool $initialized = false;

    /**
     * Exceptions that should never be reported to Sentry
     *
     * @var array<class-string<\Throwable>>
     */
    private const IGNORED_EXCEPTIONS = [
        \TYPO3\CMS\Core\Http\ImmediateResponseException::class,
        \TYPO3\CMS\Core\Error\Http\PageNotFoundException::class,
        \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException::class,
        \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException::class,
    ];

    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        $dsn = self::getDsn();

        if ($dsn === '') {
            return;
        }

        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $isDevelopment = Environment::getContext()->isDevelopment();

        $options = [
            // Core Configuration
            'dsn' => $dsn,
            'environment' => self::getEnvironment(),
            'release' => self::getRelease(),
            'server_name' => $_SERVER['SERVER_NAME'] ?? gethostname() ?: 'cli',
            'send_default_pii' => false,

            // Error Monitoring
            'sample_rate' => 1.0,
            'attach_stacktrace' => true,
            'context_lines' => 5,
            'max_breadcrumbs' => 50,
            'max_request_body_size' => 'medium',
            'ignore_exceptions' => self::IGNORED_EXCEPTIONS,

            // Performance Monitoring (Tracing)
            'traces_sample_rate' => self::getTracesSampleRate(),
            'trace_propagation_targets' => self::getTracePropagationTargets(),

            // Profiling (requires Excimer extension)
            'profiles_sample_rate' => self::getProfilesSampleRate(),

            // Metrics (new in 4.19)
            'enable_metrics' => true,

            // Spotlight for local development
            'spotlight' => $isDevelopment,

            // Callbacks
            'before_send' => static fn (Event $event, ?EventHint $hint): ?Event => self::beforeSend($event, $hint),
            'before_send_transaction' => static fn (Event $event, ?EventHint $hint): ?Event => self::beforeSendTransaction($event, $hint),
            'before_breadcrumb' => static fn (\Sentry\Breadcrumb $breadcrumb): ?\Sentry\Breadcrumb => self::beforeBreadcrumb($breadcrumb),

            // In-App Configuration
            'in_app_include' => [
                'MensCircle\\',
            ],
            'in_app_exclude' => [
                'TYPO3\\',
                'Symfony\\',
                'Doctrine\\',
                'Psr\\',
            ],
        ];

        // Add TYPO3-specific tags
        $options['tags'] = [
            'typo3.version' => $typo3Version->getVersion(),
            'typo3.context' => Environment::getContext()->__toString(),
            'php.version' => PHP_VERSION,
            'php.sapi' => PHP_SAPI,
        ];

        init($options);

        self::$initialized = true;

        // Set initial scope with TYPO3 context
        self::configureScope();
    }

    public static function captureException(\Throwable $exception): ?EventId
    {
        self::initialize();

        if (!self::isInitialized()) {
            return null;
        }

        // Check if exception should be ignored
        foreach (self::IGNORED_EXCEPTIONS as $ignoredException) {
            if ($exception instanceof $ignoredException) {
                return null;
            }
        }

        // Filter out PHP deprecations
        if (self::isDeprecation($exception)) {
            return null;
        }

        $eventId = null;

        withScope(static function (Scope $scope) use ($exception, &$eventId): void {
            self::enrichScope($scope);
            $eventId = captureException($exception);
        });

        return $eventId;
    }

    /**
     * Start a new transaction for performance monitoring
     */
    public static function startTransaction(string $name, string $operation = 'http.server'): ?\Sentry\Tracing\Transaction
    {
        self::initialize();

        if (!self::isInitialized()) {
            return null;
        }

        $hub = SentrySdk::getCurrentHub();

        $transactionContext = new TransactionContext();
        $transactionContext->setName($name);
        $transactionContext->setOp($operation);
        $transactionContext->setSource(TransactionSource::route());

        $transaction = $hub->startTransaction($transactionContext);
        $hub->setSpan($transaction);

        return $transaction;
    }

    /**
     * Start a child span within the current transaction
     */
    public static function startSpan(string $operation, string $description): ?\Sentry\Tracing\Span
    {
        $hub = SentrySdk::getCurrentHub();
        $parent = $hub->getSpan();

        if ($parent === null) {
            return null;
        }

        $context = new SpanContext();
        $context->setOp($operation);
        $context->setDescription($description);

        $span = $parent->startChild($context);
        $hub->setSpan($span);

        return $span;
    }

    /**
     * Finish a span and restore parent
     */
    public static function finishSpan(?\Sentry\Tracing\Span $span): void
    {
        if ($span === null) {
            return;
        }

        $span->finish();

        $hub = SentrySdk::getCurrentHub();
        $parent = $span->getParentSpan();

        if ($parent !== null) {
            $hub->setSpan($parent);
        }
    }

    /**
     * Add a breadcrumb for debugging context
     */
    public static function addBreadcrumb(
        string $message,
        string $category = 'default',
        string $level = 'info',
        array $data = [],
    ): void {
        if (!self::isInitialized()) {
            return;
        }

        \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
            $level,
            \Sentry\Breadcrumb::TYPE_DEFAULT,
            $category,
            $message,
            $data,
        ));
    }

    /**
     * Set user context from TYPO3 frontend user
     */
    public static function setUserContext(?int $userId = null, ?string $email = null, ?string $username = null): void
    {
        if (!self::isInitialized()) {
            return;
        }

        $hub = SentrySdk::getCurrentHub();
        $hub->configureScope(static function (Scope $scope) use ($userId, $email, $username): void {
            $userData = [];

            if ($userId !== null) {
                $userData['id'] = (string)$userId;
            }

            if ($email !== null) {
                $userData['email'] = $email;
            }

            if ($username !== null) {
                $userData['username'] = $username;
            }

            if ($userData !== []) {
                $scope->setUser($userData);
            }
        });
    }

    /**
     * Record a metric (counter, gauge, distribution)
     */
    public static function incrementCounter(string $key, float $value = 1.0, array $tags = []): void
    {
        if (!self::isInitialized()) {
            return;
        }

        \Sentry\metrics()->increment($key, $value, null, $tags);
    }

    public static function gauge(string $key, float $value, array $tags = []): void
    {
        if (!self::isInitialized()) {
            return;
        }

        \Sentry\metrics()->gauge($key, $value, null, $tags);
    }

    public static function distribution(string $key, float $value, array $tags = []): void
    {
        if (!self::isInitialized()) {
            return;
        }

        \Sentry\metrics()->distribution($key, $value, null, $tags);
    }

    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    public static function getHub(): ?HubInterface
    {
        if (!self::isInitialized()) {
            return null;
        }

        return SentrySdk::getCurrentHub();
    }

    private static function getDsn(): string
    {
        return (string)($_ENV['SENTRY_DSN'] ?? getenv('SENTRY_DSN') ?: '');
    }

    private static function getEnvironment(): string
    {
        $env = (string)($_ENV['SENTRY_ENVIRONMENT'] ?? getenv('SENTRY_ENVIRONMENT') ?: '');

        if ($env !== '') {
            return $env;
        }

        if (Environment::getContext()->isProduction()) {
            return 'production';
        }

        if (Environment::getContext()->isDevelopment()) {
            return 'development';
        }

        return 'testing';
    }

    private static function getRelease(): string
    {
        return (string)($_ENV['SENTRY_RELEASE'] ?? getenv('SENTRY_RELEASE') ?: '');
    }

    private static function getTracesSampleRate(): float
    {
        $rate = $_ENV['SENTRY_TRACES_SAMPLE_RATE'] ?? getenv('SENTRY_TRACES_SAMPLE_RATE') ?: null;

        if ($rate !== null && $rate !== false) {
            return (float)$rate;
        }

        // Default: 10% in production, 100% in development
        return Environment::getContext()->isDevelopment() ? 1.0 : 0.1;
    }

    private static function getProfilesSampleRate(): float
    {
        // Profiling requires Excimer extension
        if (!extension_loaded('excimer')) {
            return 0.0;
        }

        $rate = $_ENV['SENTRY_PROFILES_SAMPLE_RATE'] ?? getenv('SENTRY_PROFILES_SAMPLE_RATE') ?: null;

        if ($rate !== null && $rate !== false) {
            return (float)$rate;
        }

        // Default: 10% in production, 100% in development
        return Environment::getContext()->isDevelopment() ? 1.0 : 0.1;
    }

    /**
     * @return list<string>
     */
    private static function getTracePropagationTargets(): array
    {
        $targets = $_ENV['SENTRY_TRACE_PROPAGATION_TARGETS'] ?? getenv('SENTRY_TRACE_PROPAGATION_TARGETS') ?: null;

        if ($targets !== null && $targets !== false) {
            return array_map('trim', explode(',', (string)$targets));
        }

        // Default: propagate to same host
        return [
            $_SERVER['HTTP_HOST'] ?? 'localhost',
        ];
    }

    private static function configureScope(): void
    {
        $hub = SentrySdk::getCurrentHub();

        $hub->configureScope(static function (Scope $scope): void {
            // Add TYPO3-specific context
            $scope->setContext('typo3', [
                'project_path' => Environment::getProjectPath(),
                'public_path' => Environment::getPublicPath(),
                'var_path' => Environment::getVarPath(),
                'context' => Environment::getContext()->__toString(),
                'cli' => Environment::isCli(),
                'composer_mode' => Environment::isComposerMode(),
            ]);

            // Add request context if available
            if (isset($_SERVER['REQUEST_URI'])) {
                $scope->setContext('request', [
                    'uri' => $_SERVER['REQUEST_URI'],
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                    'host' => $_SERVER['HTTP_HOST'] ?? 'cli',
                ]);
            }
        });
    }

    private static function enrichScope(Scope $scope): void
    {
        // Add current TYPO3 context
        $scope->setExtra('typo3_context', Environment::getContext()->__toString());
        $scope->setExtra('request_uri', $_SERVER['REQUEST_URI'] ?? 'cli');
        $scope->setExtra('request_method', $_SERVER['REQUEST_METHOD'] ?? 'CLI');

        // Try to get frontend user context
        try {
            $context = GeneralUtility::makeInstance(Context::class);
            $userAspect = $context->getAspect('frontend.user');

            if ($userAspect->isLoggedIn()) {
                $scope->setExtra('frontend_user_id', $userAspect->get('id'));
                $scope->setExtra('frontend_user_groups', $userAspect->get('groupIds'));
            }
        } catch (\Exception) {
            // Context not available, skip
        }
    }

    private static function beforeSend(Event $event, ?EventHint $hint): ?Event
    {
        $exception = $hint?->exception;

        if ($exception === null) {
            return $event;
        }

        // Filter out exceptions that should not be reported
        foreach (self::IGNORED_EXCEPTIONS as $ignoredException) {
            if ($exception instanceof $ignoredException) {
                return null;
            }
        }

        // Filter out PHP deprecations
        if (self::isDeprecation($exception)) {
            return null;
        }

        return $event;
    }

    /**
     * Check if an exception is a PHP deprecation warning.
     *
     * Deprecations are ErrorException instances with severity E_DEPRECATED or E_USER_DEPRECATED.
     */
    private static function isDeprecation(\Throwable $exception): bool
    {
        if (!$exception instanceof \ErrorException) {
            return false;
        }

        $severity = $exception->getSeverity();

        return $severity === \E_DEPRECATED || $severity === \E_USER_DEPRECATED;
    }

    private static function beforeSendTransaction(Event $event, ?EventHint $hint): ?Event
    {
        // Filter out transactions for static assets
        $transactionName = $event->getTransaction();

        if ($transactionName !== null) {
            $ignoredPatterns = [
                '/typo3temp/',
                '/fileadmin/',
                '/_assets/',
                '.css',
                '.js',
                '.png',
                '.jpg',
                '.gif',
                '.svg',
                '.woff',
                '.woff2',
            ];

            foreach ($ignoredPatterns as $pattern) {
                if (str_contains($transactionName, $pattern)) {
                    return null;
                }
            }
        }

        return $event;
    }

    private static function beforeBreadcrumb(\Sentry\Breadcrumb $breadcrumb): ?\Sentry\Breadcrumb
    {
        // Filter out noisy breadcrumbs
        $message = $breadcrumb->getMessage();

        if ($message !== null) {
            $ignoredPatterns = [
                'SELECT * FROM sys_',
                'SELECT * FROM cache_',
            ];

            foreach ($ignoredPatterns as $pattern) {
                if (str_contains($message, $pattern)) {
                    return null;
                }
            }
        }

        return $breadcrumb;
    }
}
