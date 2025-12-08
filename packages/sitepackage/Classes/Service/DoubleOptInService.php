<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Service;

use MensCircle\Sitepackage\Domain\Model\Newsletter\Subscription;
use MensCircle\Sitepackage\Domain\Repository\Newsletter\SubscriptionRepository;
use MensCircle\Sitepackage\Enum\SubscriptionStatusEnum;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

readonly class DoubleOptInService
{
    public function __construct(
        private TokenService $tokenService,
        private SubscriptionRepository $subscriptionRepository,
    ) {
    }

    /**
     * @throws UnknownObjectException
     * @throws IllegalObjectTypeException
     */
    public function processDoubleOptIn(string $token): ?Subscription
    {
        if (!$this->tokenService->validateToken($token)) {
            return null;
        }

        $subscription = $this->subscriptionRepository->findOneBy(['doubleOptInToken' => $token]);

        if ($subscription === null) {
            return null;
        }

        $subscription->doubleOptInToken = '';
        $subscription->doubleOptInDate = new \DateTime();
        $subscription->status = SubscriptionStatusEnum::Active;

        $this->subscriptionRepository->update($subscription);

        return $subscription;
    }
}
