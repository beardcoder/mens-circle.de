<?php

declare(strict_types=1);

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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;

class SubscriptionController extends ActionController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly TokenService $tokenService,
        private readonly EmailService $emailService,
        private readonly DoubleOptInService $doubleOptInService,
        private readonly FrontendUserService $frontendUserService,
    ) {
    }

    public function formAction(?Subscription $subscription = null): ResponseInterface
    {
        $subscription ??= GeneralUtility::makeInstance(Subscription::class);
        $this->view->assign('subscription', $subscription);

        return $this->htmlResponse();
    }

    public function successAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * @throws \DateMalformedStringException
     * @throws IllegalObjectTypeException
     */
    public function subscribeAction(Subscription $subscription): ResponseInterface
    {
        $existingSubscription = $this->subscriptionRepository->findOneBy([
            'email' => $subscription->email,
        ]);

        if ($existingSubscription instanceof Subscription) {
            if ($existingSubscription->status->is(SubscriptionStatusEnum::Active)) {
                $this->addFlashMessage(
                    'Du hast unseren Newsletter bereits abonniert.',
                    '',
                    ContextualFeedbackSeverity::WARNING
                );

                return $this->redirect('form');
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

        return $this->redirect('success');
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
        $data = $universalSecureTokenService->decrypt($token);
        $email = (string) ($data['email'] ?? '');
        if ($email === '') {
            return $this->htmlResponse();
        }

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
