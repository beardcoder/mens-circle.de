<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Job;

use Beardcoder\Queue\Queue\JobInterface;
use MensCircle\Sitepackage\Domain\Model\Newsletter\Newsletter;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class SendNewsletterMailJob implements JobInterface
{
    public function __construct(private Address $emailAddress, private Newsletter $newsletter, private string $unsubscribeLink)
    {
    }

    public function handle(): void
    {
        $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class);
        $fluidEmail
            ->from(new Address('hallo@mens-circle.de', "Men's Circle Website"))
            ->subject($this->newsletter->subject)
            ->format(FluidEmail::FORMAT_BOTH)
            ->to($this->emailAddress)
            ->setTemplate('Newsletter')
            ->assign('subject', $this->newsletter->subject)
            ->assign(
                'unsubscribeLink',
                $this->unsubscribeLink,
            )
            ->assign('message', $this->newsletter->message)
        ;

        $mailer = GeneralUtility::makeInstance(Mailer::class);
        $mailer->send($fluidEmail);
    }
}
