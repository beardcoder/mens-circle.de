<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Queue\Handler;

use MensCircle\Sitepackage\Message\SendMailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsMessageHandler]
final class SendMailHandler
{
    public function __invoke(SendMailMessage $message): void
    {
        $fluidEmail = new FluidEmail()
            ->from(new Address('hallo@mens-circle.de', "Men's Circle Website"))
            ->to($message->toEmail)
            ->subject($message->subject)
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate($message->template)
            ->assignMultiple($message->variables)
        ;

        $mailer = GeneralUtility::makeInstance(Mailer::class);
        $mailer->send($fluidEmail);
    }
}
