<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Message;

use MensCircle\Sitepackage\Domain\Model\Newsletter\Newsletter;
use Symfony\Component\Messenger\Attribute\AsMessage;
use Symfony\Component\Mime\Address;

#[AsMessage]
class SendNewsletterMessage
{
    public function __construct(
        public Address $emailAddress,
        public Newsletter $newsletter,
        public string $unsubscribeLink,
    ) {
    }
}
