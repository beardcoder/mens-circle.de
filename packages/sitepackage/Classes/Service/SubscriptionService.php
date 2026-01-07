<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Service;

use MensCircle\Sitepackage\Domain\Model\Subscriber;
use MensCircle\Sitepackage\Domain\Repository\SubscriberRepository;
use MensCircle\Sitepackage\Enum\SubscriberStatus;
use Psr\Log\LoggerInterface;
use Throwable;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

final readonly class SubscriptionService
{
    public function __construct(
        private SubscriberRepository $subscriberRepository,
        private PersistenceManagerInterface $persistenceManager,
        private EmailService $emailService,
        private TokenService $tokenService,
        private SiteFinder $siteFinder,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * @return array{success: bool, message: string}
     *
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function subscribe(string $email): array
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ];
        }

        $existing = $this->subscriberRepository->findByEmail($email);

        if ($existing instanceof Subscriber) {
            if ($existing->getStatus() === SubscriberStatus::Confirmed) {
                return [
                    'success' => false,
                    'message' => 'This email is already subscribed.',
                ];
            }

            if ($existing->getStatus() === SubscriberStatus::Unsubscribed) {
                // Resubscribe
                $existing->setStatus(SubscriberStatus::Pending);
                $existing->setToken($this->tokenService->generate());
                $existing->setUnsubscribedAt(null);
                $this->subscriberRepository->update($existing);
                $this->persistenceManager->persistAll();

                $this->sendConfirmationEmail($existing);

                return [
                    'success' => true,
                    'message' => 'Please check your email to confirm your subscription.',
                ];
            }

            // Pending - resend confirmation
            $existing->setToken($this->tokenService->generate());
            $this->subscriberRepository->update($existing);
            $this->persistenceManager->persistAll();

            $this->sendConfirmationEmail($existing);

            return [
                'success' => true,
                'message' => 'We have sent you a new confirmation email.',
            ];
        }

        $subscriber = new Subscriber();
        $subscriber->setEmail($email);
        $subscriber->setToken($this->tokenService->generate());
        $subscriber->setStatus(SubscriberStatus::Pending);

        $this->subscriberRepository->add($subscriber);
        $this->persistenceManager->persistAll();

        $this->sendConfirmationEmail($subscriber);

        $this->logger->info('New newsletter subscription', ['email' => $email]);

        return [
            'success' => true,
            'message' => 'Please check your email to confirm your subscription.',
        ];
    }

    /**
     * @return array{success: bool, message: string}
     *
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function confirm(string $token): array
    {
        $subscriber = $this->subscriberRepository->findByToken($token);

        if (!$subscriber instanceof Subscriber) {
            return [
                'success' => false,
                'message' => 'Invalid or expired confirmation link.',
            ];
        }

        $subscriber->confirm();
        $this->subscriberRepository->update($subscriber);
        $this->persistenceManager->persistAll();

        // Send welcome email
        $this->emailService->send(
            template: 'NewsletterWelcome',
            subject: 'Welcome to Mens Circle Newsletter',
            to: $subscriber->getEmail(),
            variables: [
                'unsubscribeUrl' => $this->generateUnsubscribeUrl($subscriber),
            ],
        );

        $this->logger->info('Newsletter subscription confirmed', ['email' => $subscriber->getEmail()]);

        return [
            'success' => true,
            'message' => 'Your subscription has been confirmed. Welcome!',
        ];
    }

    /**
     * @return array{success: bool, message: string}
     *
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function unsubscribe(string $email, string $token): array
    {
        $subscriber = $this->subscriberRepository->findByEmail($email);

        if (!$subscriber instanceof Subscriber) {
            return [
                'success' => false,
                'message' => 'Email address not found.',
            ];
        }

        // Verify token matches a hash of the email
        $expectedToken = $this->generateUnsubscribeToken($subscriber);

        if (!hash_equals($expectedToken, $token)) {
            return [
                'success' => false,
                'message' => 'Invalid unsubscribe link.',
            ];
        }

        $subscriber->unsubscribe();
        $this->subscriberRepository->update($subscriber);
        $this->persistenceManager->persistAll();

        $this->logger->info('Newsletter unsubscribed', ['email' => $subscriber->getEmail()]);

        return [
            'success' => true,
            'message' => 'You have been unsubscribed successfully.',
        ];
    }

    private function sendConfirmationEmail(Subscriber $subscriber): void
    {
        $confirmUrl = $this->generateConfirmUrl($subscriber);

        $this->emailService->send(
            template: 'NewsletterSubscription',
            subject: 'Confirm Your Newsletter Subscription - Mens Circle',
            to: $subscriber->getEmail(),
            variables: [
                'confirmationUrl' => $confirmUrl,
            ],
        );
    }

    private function generateConfirmUrl(Subscriber $subscriber): string
    {
        $baseUrl = $this->getBaseUrl();

        return $baseUrl . '/newsletter/confirm?token=' . $subscriber->getToken();
    }

    public function generateUnsubscribeUrl(Subscriber $subscriber): string
    {
        $baseUrl = $this->getBaseUrl();
        $token = $this->generateUnsubscribeToken($subscriber);

        return $baseUrl . '/newsletter/unsubscribe?email=' . urlencode($subscriber->getEmail()) . '&token=' . $token;
    }

    private function generateUnsubscribeToken(Subscriber $subscriber): string
    {
        $secret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? 'default-key';

        return hash_hmac('sha256', $subscriber->getEmail() . $subscriber->getUid(), (string)$secret);
    }

    private function getBaseUrl(): string
    {
        try {
            $sites = $this->siteFinder->getAllSites();
            $site = $sites['main'] ?? reset($sites);

            return rtrim((string)$site->getBase());
        } catch (Throwable) {
            return 'https://mens-circle.de';
        }
    }
}
