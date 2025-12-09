<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Model\Newsletter;

use MensCircle\Sitepackage\Domain\Model\FrontendUser;
use MensCircle\Sitepackage\Enum\SubscriptionStatusEnum;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Subscription extends AbstractEntity
{
    public string $email;

    public string $firstName;

    public string $lastName;

    public ?FrontendUser $feUser = null;

    public ?\DateTime $optInDate = null;

    public ?\DateTime $optOutDate = null;

    public ?string $doubleOptInToken = null;

    public ?\DateTime $doubleOptInDate = null;

    public ?\DateTime $privacyPolicyAcceptedDate = null;

    public SubscriptionStatusEnum $status = SubscriptionStatusEnum::Pending;

    /**
     * Computed property: Full name from first and last name.
     */
    public string $name {
        get => "{$this->firstName} {$this->lastName}";
    }
}
