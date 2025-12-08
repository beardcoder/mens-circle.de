<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

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
