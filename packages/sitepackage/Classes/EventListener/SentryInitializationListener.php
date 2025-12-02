<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\EventListener;

use MensCircle\Sitepackage\Service\SentryService;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

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
    public function __invoke(BootCompletedEvent $e): void
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
        if (isset($GLOBALS['TSFE']->fe_user->user) && \is_array($GLOBALS['TSFE']->fe_user->user)) {
            $this->sentryService->setUser([
                'id' => (string) $GLOBALS['TSFE']->fe_user->user['uid'],
                'username' => $GLOBALS['TSFE']->fe_user->user['username'] ?? 'anonymous',
            ]);
        }
    }
}
