<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Service;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class FormHandlerService
{
    /** @var array<string, callable> */
    private array $handlers;

    public function __construct(
        private LoggerInterface $logger,
        private ValidationService $validationService,
        private EmailService $emailService,
        private SubscriptionService $subscriptionService,
    ) {
        $this->handlers = [
            'contact' => $this->handleContactForm(...),
            'newsletter' => $this->handleNewsletterForm(...),
        ];
    }

    /**
     * @return array{success: bool, message: string, errors?: array<string, string>}
     */
    public function handle(string $formType, ServerRequestInterface $request): array
    {
        if (!isset($this->handlers[$formType])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown form type: %s', $formType),
            );
        }

        $data = $this->parseRequestBody($request);

        return ($this->handlers[$formType])($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseRequestBody(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $body = (string)$request->getBody();

        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON payload');
            }

            return is_array($data) ? $data : [];
        }

        parse_str($body, $data);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{success: bool, message: string, errors?: array<string, string>}
     */
    private function handleContactForm(array $data): array
    {
        $rules = [
            'name' => ['required', 'min:2'],
            'email' => ['required', 'email'],
            'message' => ['required', 'min:10'],
        ];

        $errors = $this->validationService->validate($data, $rules);

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
            ];
        }

        // Send notification email to admin
        $this->emailService->send(
            template: 'Contact',
            subject: 'New Contact Form Submission',
            to: $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? 'info@mens-circle.de',
            variables: [
                'name' => $data['name'],
                'email' => $data['email'],
                'message' => $data['message'],
            ],
            replyTo: $data['email'],
        );

        // Send confirmation email to user
        $this->emailService->send(
            template: 'ContactConfirmation',
            subject: 'Thank You for Your Message - Mens Circle',
            to: $data['email'],
            variables: [
                'name' => $data['name'],
                'message' => $data['message'],
            ],
        );

        $this->logger->info('Contact form submitted', ['email' => $data['email']]);

        return [
            'success' => true,
            'message' => 'Thank you for your message. We will get back to you soon.',
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{success: bool, message: string, errors?: array<string, string>}
     */
    private function handleNewsletterForm(array $data): array
    {
        $email = $data['email'] ?? '';

        return $this->subscriptionService->subscribe($email);
    }
}
