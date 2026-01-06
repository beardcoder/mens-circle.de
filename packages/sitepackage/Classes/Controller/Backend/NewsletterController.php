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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

#[AsController]
final class NewsletterController extends ActionController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly NewsletterRepository $newsletterRepository,
        protected readonly SubscriberRepository $subscriberRepository,
        protected readonly NewsletterService $newsletterService,
        protected readonly PersistenceManagerInterface $persistenceManager,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($this->request);

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

    public function subscribersAction(): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($this->request);

        $subscribers = $this->subscriberRepository->findAll();
        $stats = $this->subscriberRepository->getStatistics();

        $view->assignMultiple([
            'subscribers' => $subscribers,
            'stats' => $stats,
        ]);

        $view->setTitle('Newsletter Subscribers');

        return $view->renderResponse('Backend/Newsletter/Subscribers');
    }

    public function createAction(): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($this->request);

        $view->assign('newsletter', new Newsletter());
        $view->assign('isNew', true);
        $view->setTitle('Create Newsletter');

        return $view->renderResponse('Backend/Newsletter/Edit');
    }

    public function editAction(?Newsletter $newsletter = null): ResponseInterface
    {
        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('index');
        }

        $view = $this->moduleTemplateFactory->create($this->request);

        $view->assignMultiple([
            'newsletter' => $newsletter,
            'isNew' => false,
        ]);
        $view->setTitle('Edit Newsletter');

        return $view->renderResponse('Backend/Newsletter/Edit');
    }

    public function saveAction(?Newsletter $newsletter = null): ResponseInterface
    {
        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('index');
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
            '',
            ContextualFeedbackSeverity::OK,
        );

        return $this->redirect('index');
    }

    public function sendAction(?Newsletter $newsletter = null): ResponseInterface
    {
        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('index');
        }

        $result = $this->newsletterService->send($newsletter);

        $this->addFlashMessage(
            $result['message'],
            '',
            $result['success'] ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
        );

        return $this->redirect('index');
    }

    public function sendTestAction(?Newsletter $newsletter = null): ResponseInterface
    {
        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('index');
        }

        $email = $this->request->hasArgument('email') ? (string)$this->request->getArgument('email') : '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlashMessage('Please enter a valid email address.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('edit', null, null, ['newsletter' => $newsletter->getUid()]);
        }

        $success = $this->newsletterService->sendTest($newsletter, $email);

        $this->addFlashMessage(
            $success ? 'Test email sent to ' . $email : 'Failed to send test email.',
            '',
            $success ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR,
        );

        return $this->redirect('edit', null, null, ['newsletter' => $newsletter->getUid()]);
    }

    public function deleteAction(?Newsletter $newsletter = null): ResponseInterface
    {
        if ($newsletter === null) {
            $this->addFlashMessage('Newsletter not found.', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('index');
        }

        $this->newsletterRepository->remove($newsletter);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage('Newsletter deleted successfully.', '', ContextualFeedbackSeverity::OK);

        return $this->redirect('index');
    }
}
