<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Model\Newsletter;

use MensCircle\Sitepackage\Domain\Model\FrontendUser;
use MensCircle\Sitepackage\Enum\SubscriptionStatusEnum;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Subscription extends AbstractEntity
{
    #[Validate(['validator' => 'EmailAddress'])]
    public string $email;

    #[Validate(['validator' => 'NotEmpty'])]
    public string $firstName;

    #[Validate(['validator' => 'NotEmpty'])]
    public string $lastName;

    public ?FrontendUser $feUser = null;

    public \DateTime|null $optInDate = null;

    public \DateTime|null $optOutDate = null;

    public ?string $doubleOptInToken = null;

    public \DateTime|null $doubleOptInDate = null;

    public \DateTime|null $privacyPolicyAcceptedDate = null;

    public SubscriptionStatusEnum $status = SubscriptionStatusEnum::Pending;

    public function getName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
