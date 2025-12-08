<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class EventNotification extends AbstractEntity
{
    public ?Event $event = null;

    public string $subject = '';

    public string $message = '';
}
