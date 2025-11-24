<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Queue\Handler;

use MensCircle\Sitepackage\Message\SendNewsletterMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsMessageHandler]
final class SendNewsletterHandler
{
    public function __invoke(SendNewsletterMessage $message): void
    {
        $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
        $fluidEmail
            ->from(new Address('hallo@mens-circle.de', "Men's Circle Website"))
            ->subject($message->newsletter->subject)
            ->format(FluidEmail::FORMAT_BOTH)
            ->to($message->emailAddress)
            ->setTemplate('Newsletter')
            ->assign('subject', $message->newsletter->subject)
            ->assign(
                'unsubscribeLink',
                $message->unsubscribeLink,
            )
            ->assign('message', $message->newsletter->message)
        ;

        $mailer = GeneralUtility::makeInstance(Mailer::class);
        $mailer->send($fluidEmail);
    }
}
