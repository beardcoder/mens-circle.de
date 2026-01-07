<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Controller\Backend;

use MensCircle\Sitepackage\Domain\Model\Newsletter;
use MensCircle\Sitepackage\Domain\Repository\NewsletterRepository;
use MensCircle\Sitepackage\Domain\Repository\SubscriberRepository;
use MensCircle\Sitepackage\Service\NewsletterService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Backend module controller for newsletter management
 */
#[AsController]
final class NewsletterController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly NewsletterRepository $newsletterRepository,
        protected readonly SubscriberRepository $subscriberRepository,
        protected readonly NewsletterService $newsletterService,
        protected readonly PersistenceManagerInterface $persistenceManager,
        protected readonly UriBuilder $uriBuilder,
        protected readonly FlashMessageService $flashMessageService,
    ) {}

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);

        $newsletters = $this->newsletterRepository->findAll();
        $subscriberStats = $this->subscriberRepository->getStatistics();
        $newsletterStats = $this->newsletterRepository->getStatistics();

        $view->assignMultiple([
            'newsletters' => $newsletters,
            'subscriberStats' => $subscriberStats,
            'newsletterStats' => $newsletterStats,
        ]);

        $view->setTitle('Newsletter Management');

        return $view->renderResponse('Backend/Newsletter/Index');
    }

    public function subscribersAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);

        $subscribers = $this->subscriberRepository->findAll();
        $stats = $this->subscriberRepository->getStatistics();

        $view->assignMultiple([
            'subscribers' => $subscribers,
            'stats' => $stats,
        ]);

        $view->setTitle('Newsletter Subscribers');

        return $view->renderResponse('Backend/Newsletter/Subscribers');
    }

    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);

        $view->assign('newsletter', new Newsletter());
        $view->assign('isNew', true);
        $view->setTitle('Create Newsletter');

        return $view->renderResponse('Backend/Newsletter/Edit');
    }

    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $newsletterUid = (int)($queryParams['newsletter'] ?? 0);
        $newsletter = $newsletterUid > 0 ? $this->newsletterRepository->findByUid($newsletterUid) : null;

        if (!$newsletter instanceof Newsletter) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToRoute($request, 'index');
        }

        $view = $this->moduleTemplateFactory->create($request);

        $view->assignMultiple([
            'newsletter' => $newsletter,
            'isNew' => false,
        ]);
        $view->setTitle('Edit Newsletter');

        return $view->renderResponse('Backend/Newsletter/Edit');
    }

    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $newsletterUid = (int)($queryParams['newsletter'] ?? 0);
        $newsletter = $newsletterUid > 0 ? $this->newsletterRepository->findByUid($newsletterUid) : null;

        if (!$newsletter instanceof Newsletter) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToRoute($request, 'index');
        }

        $isUpdate = $newsletter->getUid() > 0;

        if ($isUpdate) {
            $this->newsletterRepository->update($newsletter);
        } else {
            $this->newsletterRepository->add($newsletter);
        }

        $this->persistenceManager->persistAll();

        $this->addFlashMessage(
            $isUpdate ? 'Newsletter updated successfully.' : 'Newsletter created successfully.',
            ContextualFeedbackSeverity::OK,
        );

        return $this->redirectToRoute($request, 'index');
    }

    public function sendAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $newsletterUid = (int)($queryParams['newsletter'] ?? 0);
        $newsletter = $newsletterUid > 0 ? $this->newsletterRepository->findByUid($newsletterUid) : null;

        if (!$newsletter instanceof Newsletter) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToRoute($request, 'index');
        }

        $result = $this->newsletterService->send($newsletter);

        $this->addFlashMessage(
            $result['message'],
            $result['success'] ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
        );

        return $this->redirectToRoute($request, 'index');
    }

    public function sendTestAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $newsletterUid = (int)($queryParams['newsletter'] ?? 0);
        $newsletter = $newsletterUid > 0 ? $this->newsletterRepository->findByUid($newsletterUid) : null;

        if (!$newsletter instanceof Newsletter) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToRoute($request, 'index');
        }

        $email = $queryParams['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlashMessage('Please enter a valid email address.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToRoute($request, 'edit', ['newsletter' => $newsletter->getUid()]);
        }

        $success = $this->newsletterService->sendTest($newsletter, $email);

        $this->addFlashMessage(
            $success ? 'Test email sent to ' . $email : 'Failed to send test email.',
            $success ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
        );

        return $this->redirectToRoute($request, 'edit', ['newsletter' => $newsletter->getUid()]);
    }

    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $newsletterUid = (int)($queryParams['newsletter'] ?? 0);
        $newsletter = $newsletterUid > 0 ? $this->newsletterRepository->findByUid($newsletterUid) : null;

        if (!$newsletter instanceof Newsletter) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToRoute($request, 'index');
        }

        $this->newsletterRepository->remove($newsletter);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage('Newsletter deleted successfully.', ContextualFeedbackSeverity::OK);

        return $this->redirectToRoute($request, 'index');
    }

    /**
     * Add a flash message to the queue
     */
    protected function addFlashMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::INFO): void
    {
        $flashMessage = new FlashMessage(
            $message,
            '',
            $severity,
        );
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);
    }

    /**
     * Redirect to a route within the same module
     */
    protected function redirectToRoute(ServerRequestInterface $request, string $route, array $parameters = []): ResponseInterface
    {
        $routeIdentifier = $request->getAttribute('route');
        $moduleName = $routeIdentifier?->getOption('moduleName') ?? 'menscircle_newsletter';

        $uri = $this->uriBuilder->buildUriFromRoute(
            $moduleName . '_' . $route,
            $parameters
        );

        return new RedirectResponse($uri);
    }
}
