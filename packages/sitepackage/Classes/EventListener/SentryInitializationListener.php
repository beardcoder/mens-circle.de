<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\EventListener;

use MensCircle\Sitepackage\Service\SentryService;

/**
 * Event listener for initializing Sentry context early in the request lifecycle.
 */
final readonly class SentryInitializationListener
{
    public function __construct(
        private SentryService $sentryService,
    ) {
    }

    /**
     * Initialize Sentry context when TYPO3 is fully booted.
     */
    public function __invoke(): void
    {
        if (!$this->sentryService->isInitialized()) {
            return;
        }

        // Set TYPO3-specific context
        $this->sentryService->setTags([
            'typo3.version' => new \TYPO3\CMS\Core\Information\Typo3Version()->getVersion(),
            'php.version' => \PHP_VERSION,
            'context' => getenv('TYPO3_CONTEXT') ?: 'Production',
        ]);
        // Add backend user context if available
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->user) {
            $this->sentryService->setUser([
                'id' => (string) $GLOBALS['BE_USER']->user['uid'],
                'username' => $GLOBALS['BE_USER']->user['username'],
                'email' => $GLOBALS['BE_USER']->user['email'] ?? null,
            ]);
        }
        // Add frontend user context if available
        if (isset($GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->user) && \is_array($GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->user)) {
            $this->sentryService->setUser([
                'id' => (string) $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->user['uid'],
                'username' => $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.user')->user['username'] ?? 'anonymous',
            ]);
        }
    }
}
