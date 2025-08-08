<?php

namespace MensCircle\Sitepackage\Controller;

use MensCircle\Sitepackage\Domain\Model\Newsletter\Subscription;
use MensCircle\Sitepackage\Domain\Repository\Newsletter\SubscriptionRepository;
use MensCircle\Sitepackage\Enum\SubscriptionStatusEnum;
use MensCircle\Sitepackage\Service\DoubleOptInService;
use MensCircle\Sitepackage\Service\EmailService;
use MensCircle\Sitepackage\Service\FrontendUserService;
use MensCircle\Sitepackage\Service\TokenService;
use MensCircle\Sitepackage\Service\UniversalSecureTokenService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class SubscriptionController extends ActionController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly TokenService $tokenService,
        private readonly EmailService $emailService,
        private readonly DoubleOptInService $doubleOptInService,
        private readonly FrontendUserService $frontendUserService,
    )
    {
    }

    public function formAction(?Subscription $subscription = null): ResponseInterface
    {
        $subscription ??= GeneralUtility::makeInstance(Subscription::class);
        $this->view->assign('subscription', $subscription);
        return $this->htmlResponse();
    }

    public function subscribeAction(Subscription $subscription): ResponseInterface
    {
        $existingSubscription = $this->subscriptionRepository->findOneBy([
            'email' => $subscription->email,
        ]);

        if ($existingSubscription instanceof Subscription) {
            if ($existingSubscription->status->is(SubscriptionStatusEnum::Active)) {
                return $this->htmlResponse();
            }

            $this->subscriptionRepository->remove($existingSubscription);
        }

        $feUser = $this->frontendUserService->mapToFrontendUser($subscription);
        $subscription->feUser = $feUser;

        $subscription->doubleOptInToken = $this->tokenService->generateToken();
        $subscription->optInDate = new \DateTime();
        $this->subscriptionRepository->add($subscription);
        $this->emailService->sendMail(
            $subscription->email,
            'doubleOptIn',
            [
                'subscription' => $subscription,
            ],
            'Bestätige deine Anmeldung für den Newsletter',
            $this->request,
        );

        return $this->htmlResponse();
    }

    public function doubleOptInAction(string $token): ResponseInterface
    {
        $subscription = $this->doubleOptInService->processDoubleOptIn($token);
        $this->view->assign('subscription', $subscription);
        return $this->htmlResponse();
    }

    public function unsubscribeAction(string $token): ResponseInterface
    {
        $universalSecureTokenService = GeneralUtility::makeInstance(UniversalSecureTokenService::class);
        $email = $universalSecureTokenService->decrypt($token)['email'];
        $subscription = $this->subscriptionRepository->findOneBy([
            'email' => $email,
        ]);
        if ($subscription instanceof Subscription) {
            $subscription->optOutDate = new \DateTime();
            $subscription->status = SubscriptionStatusEnum::Unsubscribed;
            $this->subscriptionRepository->update($subscription);
        }
        return $this->htmlResponse();
    }
}
