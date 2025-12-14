<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Log\Writer;

use MensCircle\Sitepackage\Error\SentryService;
use Psr\Log\LogLevel;
use Sentry\Severity;
use Sentry\State\Scope;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\AbstractWriter;

use function Sentry\captureMessage;
use function Sentry\withScope;

/**
 * TYPO3 Log Writer that sends log entries to Sentry.
 *
 * Features:
 * - Maps TYPO3 log levels to Sentry severity
 * - Captures exceptions with full stack traces
 * - Adds TYPO3-specific context (component, request ID)
 * - Supports fingerprinting for better issue grouping
 * - Adds breadcrumbs for log entries below configured level
 */
final class SentryLogWriter extends AbstractWriter
{
    /**
     * Minimum log level to send to Sentry (default: ERROR)
     * Lower levels are added as breadcrumbs.
     */
    private string $minimumLevel = LogLevel::ERROR;

    /**
     * Whether to add lower-level logs as breadcrumbs.
     */
    private bool $addBreadcrumbs = true;

    /**
     * Components to ignore (regex patterns).
     *
     * @var list<string>
     */
    private array $ignoredComponents = [
        '/^TYPO3\.CMS\.Core\.Cache/',
        '/^TYPO3\.CMS\.Core\.Database\.Query/',
    ];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['minimumLevel'])) {
            $this->minimumLevel = $options['minimumLevel'];
        }

        if (isset($options['addBreadcrumbs'])) {
            $this->addBreadcrumbs = $options['addBreadcrumbs'];
        }

        if (isset($options['ignoredComponents']) && \is_array($options['ignoredComponents'])) {
            $this->ignoredComponents = $options['ignoredComponents'];
        }
    }

    public function writeLog(LogRecord $record): self
    {
        SentryService::initialize();

        if (!SentryService::isInitialized()) {
            return $this;
        }

        // Check if component should be ignored
        if ($this->shouldIgnoreComponent($record->getComponent())) {
            return $this;
        }

        $data = $record->getData();
        $exception = $data['exception'] ?? null;

        // Handle exceptions specially
        if ($exception instanceof \Throwable) {
            SentryService::captureException($exception);

            return $this;
        }

        // Check log level
        if ($record->getLevel() > $this->minimumLevel) {
            // Add as breadcrumb if enabled
            if ($this->addBreadcrumbs) {
                $this->addAsBreadcrumb($record);
            }

            return $this;
        }

        // Capture as message
        $this->captureLogRecord($record);

        return $this;
    }

    private function captureLogRecord(LogRecord $record): void
    {
        withScope(function (Scope $scope) use ($record): void {
            $scope->setLevel($this->mapLogLevel($record->getLevel()));

            // Set fingerprint for better grouping
            $scope->setFingerprint([
                $record->getComponent(),
                $record->getMessage(),
            ]);

            // Add context
            $scope->setTag('log.component', $record->getComponent());
            $scope->setExtra('request_id', $record->getRequestId());

            $data = $record->getData();

            if ($data !== []) {
                // Filter sensitive data
                $filteredData = $this->filterSensitiveData($data);
                $scope->setExtra('log_data', $filteredData);
            }

            captureMessage($record->getMessage());
        });
    }

    private function addAsBreadcrumb(LogRecord $record): void
    {
        $level = match ($record->getLevel()) {
            LogLevel::DEBUG => 'debug',
            LogLevel::INFO, LogLevel::NOTICE => 'info',
            LogLevel::WARNING => 'warning',
            LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY => 'error',
            default => 'info',
        };

        $data = $record->getData();

        if ($data !== []) {
            $data = $this->filterSensitiveData($data);
        }

        SentryService::addBreadcrumb(
            $record->getMessage(),
            $record->getComponent(),
            $level,
            $data,
        );
    }

    private function mapLogLevel(string $level): Severity
    {
        return match ($level) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => Severity::fatal(),
            LogLevel::ERROR => Severity::error(),
            LogLevel::WARNING => Severity::warning(),
            LogLevel::NOTICE, LogLevel::INFO => Severity::info(),
            default => Severity::debug(),
        };
    }

    private function shouldIgnoreComponent(string $component): bool
    {
        foreach ($this->ignoredComponents as $pattern) {
            if (preg_match($pattern, $component) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter out sensitive data from log context.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'passwd',
            'secret',
            'token',
            'api_key',
            'apikey',
            'authorization',
            'auth',
            'credentials',
            'private_key',
            'privatekey',
        ];

        $filtered = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // Check if key contains sensitive keywords
            $isSensitive = false;

            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;

                    break;
                }
            }

            if ($isSensitive) {
                $filtered[$key] = '[FILTERED]';
            } elseif (\is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
