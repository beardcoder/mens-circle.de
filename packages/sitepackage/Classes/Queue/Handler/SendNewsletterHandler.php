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
    public function __invoke(SendNewsletterMessage $sendNewsletterMessage): void
    {
        $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
        $fluidEmail
            ->from(new Address('hallo@mens-circle.de', "Men's Circle Website"))
            ->subject($sendNewsletterMessage->newsletter->subject)
            ->format(FluidEmail::FORMAT_BOTH)
            ->to($sendNewsletterMessage->emailAddress)
            ->setTemplate('Newsletter')
            ->assign('subject', $sendNewsletterMessage->newsletter->subject)
            ->assign(
                'unsubscribeLink',
                $sendNewsletterMessage->unsubscribeLink,
            )
            ->assign('message', $sendNewsletterMessage->newsletter->message)
        ;

        $mailer = GeneralUtility::makeInstance(Mailer::class);
        $mailer->send($fluidEmail);
    }
}
