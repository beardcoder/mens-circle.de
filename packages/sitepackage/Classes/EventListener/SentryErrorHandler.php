<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\EventListener;

use MensCircle\Sitepackage\Service\SentryService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Error\Http\AbstractServerErrorException;

/**
 * Event listener for catching and reporting errors to Sentry.
 *
 * This listener catches exceptions that occur during request processing
 * and reports them to Sentry for monitoring and analysis.
 */
final class SentryErrorHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SentryService $sentryService,
    ) {
    }

    /**
     * Handle an exception and report it to Sentry.
     */
    public function handleException(\Throwable $throwable): void
    {
        // Skip certain exceptions that are not errors
        if ($this->shouldSkipException($throwable)) {
            return;
        }

        // Capture the exception in Sentry
        $eventId = $this->sentryService->captureException($throwable);

        if ($eventId !== null) {
            $this->logger?->debug('Exception captured in Sentry', [
                'eventId' => $eventId,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Determine if an exception should be skipped from reporting.
     */
    private function shouldSkipException(\Throwable $throwable): bool
    {
        // Skip HTTP 404 and other client errors (4xx)
        if ($throwable instanceof AbstractServerErrorException) {
            $statusCode = $throwable->getCode();
            if ($statusCode >= 400 && $statusCode < 500) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add request context to Sentry before capturing.
     */
    public function addRequestContext(): void
    {
        if (!$this->sentryService->isInitialized()) {
            return;
        }

        // Add TYPO3-specific context
        $this->sentryService->setTags([
            'typo3.version' => new \TYPO3\CMS\Core\Information\Typo3Version()->getVersion(),
            'php.version' => \PHP_VERSION,
        ]);

        // Add request information if available
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->sentryService->setExtra([
                'request_uri' => $_SERVER['REQUEST_URI'],
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
        }
    }
}
