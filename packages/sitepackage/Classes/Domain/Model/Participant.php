<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\Validate;
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
    public string $email;

    public ?FrontendUser $feUser = null;

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getFeUser(): ?FrontendUser
    {
        return $this->feUser;
    }

    public function setFeUser(?FrontendUser $frontendUser): void
    {
        $this->feUser = $frontendUser;
    }
}
