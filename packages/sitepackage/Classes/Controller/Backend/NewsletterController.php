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
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

#[AsController]
final readonly class NewsletterController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private NewsletterRepository $newsletterRepository,
        private SubscriberRepository $subscriberRepository,
        private NewsletterService $newsletterService,
        private PersistenceManagerInterface $persistenceManager,
        private FlashMessageService $flashMessageService,
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
        $view = $this->moduleTemplateFactory->create($request);

        $newsletterId = (int)($request->getQueryParams()['newsletter'] ?? 0);
        $newsletter = $this->newsletterRepository->findByUid($newsletterId);

        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);

            return new RedirectResponse($this->getModuleUri($request));
        }

        $view->assignMultiple([
            'newsletter' => $newsletter,
            'isNew' => false,
        ]);
        $view->setTitle('Edit Newsletter');

        return $view->renderResponse('Backend/Newsletter/Edit');
    }

    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $data = $body['newsletter'] ?? [];
        $newsletterId = (int)($data['uid'] ?? 0);

        if ($newsletterId > 0) {
            $newsletter = $this->newsletterRepository->findByUid($newsletterId);

            if ($newsletter === null) {
                $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);

                return new RedirectResponse($this->getModuleUri($request));
            }
        } else {
            $newsletter = new Newsletter();
        }

        $newsletter->setSubject($data['subject'] ?? '');
        $newsletter->setPreheader($data['preheader'] ?? '');
        $newsletter->setContent($data['content'] ?? '');

        if ($newsletterId > 0) {
            $this->newsletterRepository->update($newsletter);
        } else {
            $this->newsletterRepository->add($newsletter);
        }

        $this->persistenceManager->persistAll();

        $this->addFlashMessage(
            $newsletterId > 0 ? 'Newsletter updated successfully.' : 'Newsletter created successfully.',
            ContextualFeedbackSeverity::OK,
        );

        return new RedirectResponse($this->getModuleUri($request));
    }

    public function sendAction(ServerRequestInterface $request): ResponseInterface
    {
        $newsletterId = (int)($request->getQueryParams()['newsletter'] ?? 0);
        $newsletter = $this->newsletterRepository->findByUid($newsletterId);

        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);

            return new RedirectResponse($this->getModuleUri($request));
        }

        $result = $this->newsletterService->send($newsletter);

        $this->addFlashMessage(
            $result['message'],
            $result['success'] ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
        );

        return new RedirectResponse($this->getModuleUri($request));
    }

    public function sendTestAction(ServerRequestInterface $request): ResponseInterface
    {
        $newsletterId = (int)($request->getQueryParams()['newsletter'] ?? 0);
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';

        $newsletter = $this->newsletterRepository->findByUid($newsletterId);

        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);

            return new RedirectResponse($this->getModuleUri($request));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlashMessage('Please enter a valid email address.', ContextualFeedbackSeverity::ERROR);

            return new RedirectResponse($this->getModuleUri($request, 'edit', ['newsletter' => $newsletterId]));
        }

        $success = $this->newsletterService->sendTest($newsletter, $email);

        $this->addFlashMessage(
            $success ? 'Test email sent to ' . $email : 'Failed to send test email.',
            $success ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
        );

        return new RedirectResponse($this->getModuleUri($request, 'edit', ['newsletter' => $newsletterId]));
    }

    public function deleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $newsletterId = (int)($request->getQueryParams()['newsletter'] ?? 0);
        $newsletter = $this->newsletterRepository->findByUid($newsletterId);

        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', ContextualFeedbackSeverity::ERROR);

            return new RedirectResponse($this->getModuleUri($request));
        }

        $this->newsletterRepository->remove($newsletter);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage('Newsletter deleted successfully.', ContextualFeedbackSeverity::OK);

        return new RedirectResponse($this->getModuleUri($request));
    }

    private function addFlashMessage(string $message, ContextualFeedbackSeverity $severity): void
    {
        $flashMessage = new FlashMessage($message, '', $severity, true);
        $this->flashMessageService->getMessageQueueByIdentifier()->addMessage($flashMessage);
    }

    private function getModuleUri(ServerRequestInterface $request, string $route = '_default', array $params = []): string
    {
        $uriBuilder = $request->getAttribute('backend.uriBuilder');

        return (string)$uriBuilder->buildUriFromRoute('menscircle_newsletter.' . $route, $params);
    }
}
