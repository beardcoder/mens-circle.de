<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Service;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;

readonly class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {}

    public function sendMail(
        string $toEmail,
        string $template,
        array $variables,
        string $subject,
        ServerRequestInterface $serverRequest,
    ): void {
        $fluidEmail = new FluidEmail()
                ->to($toEmail)
                ->from(new Address('hallo@mens-circle.de', 'Men\'s Circle Website'))
                ->subject($subject)
                ->setRequest($serverRequest)
                ->format(FluidEmail::FORMAT_BOTH)
                ->setTemplate($template)
                ->assignMultiple($variables);

        try {
            $this->mailer->send($fluidEmail);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send Double Opt-In email: ' . $e->getMessage());
        }
    }
}
