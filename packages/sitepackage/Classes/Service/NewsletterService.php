<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Service;

use MensCircle\Sitepackage\Domain\Model\Newsletter;
use MensCircle\Sitepackage\Domain\Model\Subscriber;
use MensCircle\Sitepackage\Domain\Repository\NewsletterRepository;
use MensCircle\Sitepackage\Domain\Repository\SubscriberRepository;
use MensCircle\Sitepackage\Enum\NewsletterStatus;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

final readonly class NewsletterService
{
    public function __construct(
        private NewsletterRepository $newsletterRepository,
        private SubscriberRepository $subscriberRepository,
        private SubscriptionService $subscriptionService,
        private EmailService $emailService,
        private PersistenceManagerInterface $persistenceManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array{success: bool, message: string, sent?: int, failed?: int}
     */
    public function send(Newsletter $newsletter): array
    {
        if (!$newsletter->canSend()) {
            return [
                'success' => false,
                'message' => 'Newsletter cannot be sent in current status.',
            ];
        }

        $subscribers = $this->subscriberRepository->findActive();
        $count = $subscribers->count();

        if ($count === 0) {
            return [
                'success' => false,
                'message' => 'No active subscribers found.',
            ];
        }

        $newsletter->markAsSending($count);
        $this->newsletterRepository->update($newsletter);
        $this->persistenceManager->persistAll();

        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            $success = $this->sendToSubscriber($newsletter, $subscriber);

            if ($success) {
                $sent++;
                $newsletter->incrementSentCount();
            } else {
                $failed++;
                $newsletter->incrementFailedCount();
            }

            // Persist progress every 10 emails
            if (($sent + $failed) % 10 === 0) {
                $this->newsletterRepository->update($newsletter);
                $this->persistenceManager->persistAll();
            }
        }

        if ($failed > 0 && $sent === 0) {
            $newsletter->markAsFailed();
        } else {
            $newsletter->markAsSent();
        }

        $this->newsletterRepository->update($newsletter);
        $this->persistenceManager->persistAll();

        $this->logger->info('Newsletter sent', [
            'newsletter' => $newsletter->getUid(),
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return [
            'success' => true,
            'message' => sprintf('Newsletter sent to %d subscribers.', $sent),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    public function sendTest(Newsletter $newsletter, string $email): bool
    {
        return $this->emailService->send(
            template: 'Newsletter',
            subject: '[TEST] ' . $newsletter->getSubject(),
            to: $email,
            variables: [
                'newsletter' => $newsletter,
                'preheader' => $newsletter->getPreheader(),
                'content' => $newsletter->getContent(),
                'unsubscribeUrl' => '#',
                'isTest' => true,
            ],
        );
    }

    private function sendToSubscriber(Newsletter $newsletter, Subscriber $subscriber): bool
    {
        try {
            return $this->emailService->send(
                template: 'Newsletter',
                subject: $newsletter->getSubject(),
                to: $subscriber->getEmail(),
                variables: [
                    'newsletter' => $newsletter,
                    'preheader' => $newsletter->getPreheader(),
                    'content' => $newsletter->getContent(),
                    'unsubscribeUrl' => $this->subscriptionService->generateUnsubscribeUrl($subscriber),
                    'isTest' => false,
                ],
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send newsletter to subscriber', [
                'newsletter' => $newsletter->getUid(),
                'subscriber' => $subscriber->getEmail(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function create(string $subject, string $content, string $preheader = ''): Newsletter
    {
        $newsletter = new Newsletter();
        $newsletter->setSubject($subject);
        $newsletter->setContent($content);
        $newsletter->setPreheader($preheader);
        $newsletter->setStatus(NewsletterStatus::Draft);

        $this->newsletterRepository->add($newsletter);
        $this->persistenceManager->persistAll();

        return $newsletter;
    }

    public function schedule(Newsletter $newsletter, \DateTimeImmutable $scheduledAt): void
    {
        $newsletter->setScheduledAt($scheduledAt);
        $newsletter->setStatus(NewsletterStatus::Scheduled);

        $this->newsletterRepository->update($newsletter);
        $this->persistenceManager->persistAll();
    }

    public function processScheduled(): int
    {
        $scheduledNewsletters = $this->newsletterRepository->findScheduled();
        $processed = 0;

        foreach ($scheduledNewsletters as $newsletter) {
            $result = $this->send($newsletter);

            if ($result['success']) {
                $processed++;
            }
        }

        return $processed;
    }
}
