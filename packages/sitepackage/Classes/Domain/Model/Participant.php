<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Model;

use TYPO3\CMS\Extbase\Attribute\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Participant extends AbstractEntity
{
    public ?Event $event = null;

    #[Validate(validator: 'NotEmpty')]
    public string $firstName;

    #[Validate(validator: 'NotEmpty')]
    public string $lastName;

    #[Validate(validator: 'NotEmpty')]
    #[Validate(validator: 'EmailAddress')]
    public string $email {
        get => $this->email;
        set {
            $this->email = $value;
        }
    }

    public ?FrontendUser $feUser = null {
        get => $this->feUser;
        set {
            $this->feUser = $value;
        }
    }

    public string $name {
        get => "{$this->firstName} {$this->lastName}";
    }
}
