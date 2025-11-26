<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
class SendMailMessage
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public string $toEmail,
        public string $template,
        public array $variables,
        public string $subject,
    ) {
    }
}
