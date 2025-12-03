<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;

use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\init;

/**
 * Service for Sentry error tracking and monitoring.
 *
 * This service initializes Sentry with configuration from environment variables
 * and provides methods for capturing exceptions and messages.
 */
final class SentryService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private bool $initialized = false;

    /**
     * Initialize Sentry with configuration from environment variables.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $dsn = getenv('SENTRY_DSN');
        $enabled = filter_var(getenv('SENTRY_ENABLED') ?: 'true', \FILTER_VALIDATE_BOOLEAN);

        if (empty($dsn) || !$enabled) {
            $this->logger?->info('Sentry is disabled or DSN is not configured');

            return;
        }

        try {
            $config = [
                'dsn' => $dsn,
                'environment' => getenv('SENTRY_ENVIRONMENT') ?: getenv('TYPO3_CONTEXT') ?: 'Production',
                'traces_sample_rate' => (float) (getenv('SENTRY_TRACES_SAMPLE_RATE') ?: 1.0),
            ];

            init($config);
            $this->initialized = true;
            $this->logger?->info('Sentry initialized successfully', ['environment' => $config['environment']]);
        } catch (\Throwable $throwable) {
            $this->logger?->error('Failed to initialize Sentry', [
                'exception' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Check if Sentry is initialized and ready to use.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Capture an exception in Sentry.
     */
    public function captureException(\Throwable $exception): ?string
    {
        if (!$this->initialized) {
            return null;
        }

        try {
            return captureException($exception);
        } catch (\Throwable $throwable) {
            $this->logger?->error('Failed to capture exception in Sentry', [
                'exception' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Capture a message in Sentry.
     */
    public function captureMessage(string $message, string $level = 'info'): ?string
    {
        if (!$this->initialized) {
            return null;
        }

        try {
            return captureMessage($message);
        } catch (\Throwable $throwable) {
            $this->logger?->error('Failed to capture message in Sentry', [
                'exception' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get the current Sentry hub.
     */
    public function getHub(): ?HubInterface
    {
        if (!$this->initialized) {
            return null;
        }

        return SentrySdk::getCurrentHub();
    }

    /**
     * Add user context to Sentry.
     *
     * @param array<string, mixed> $userData
     */
    public function setUser(array $userData): void
    {
        if (!$this->initialized) {
            return;
        }

        $hub = $this->getHub();
        if ($hub instanceof HubInterface) {
            $hub->configureScope(static function ($scope) use ($userData): void {
                $scope->setUser($userData);
            });
        }
    }

    /**
     * Add extra context to Sentry.
     *
     * @param array<string, mixed> $extra
     */
    public function setExtra(array $extra): void
    {
        if (!$this->initialized) {
            return;
        }

        $hub = $this->getHub();
        if ($hub instanceof HubInterface) {
            $hub->configureScope(static function ($scope) use ($extra): void {
                foreach ($extra as $key => $value) {
                    $scope->setExtra($key, $value);
                }
            });
        }
    }

    /**
     * Add tags to Sentry.
     *
     * @param array<string, string> $tags
     */
    public function setTags(array $tags): void
    {
        if (!$this->initialized) {
            return;
        }

        $hub = $this->getHub();
        if ($hub instanceof HubInterface) {
            $hub->configureScope(static function ($scope) use ($tags): void {
                foreach ($tags as $key => $value) {
                    $scope->setTag($key, $value);
                }
            });
        }
    }
}
