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
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

class SubscriptionController extends ActionController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly TokenService $tokenService,
        private readonly EmailService $emailService,
        private readonly DoubleOptInService $doubleOptInService,
        private readonly FrontendUserService $frontendUserService,
    ) {}

    public function formAction(?Subscription $subscription = null): ResponseInterface
    {
        $subscription ??= GeneralUtility::makeInstance(Subscription::class);
        $this->view->assign('subscription', $subscription);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws \JsonException
     */
    public function subscribeAction(Subscription $subscription): ResponseInterface
    {
        $validationErrors = $this->validateSubscription($subscription);

        if ($validationErrors !== []) {
            return $this->jsonResponse(json_encode([
                'success' => false,
                'errors' => $validationErrors,
                'message' => 'Es sind Fehler aufgetreten. Bitte korrigiere die Eingaben.',
            ], JSON_THROW_ON_ERROR));
        }

        $existingSubscription = $this->subscriptionRepository->findOneBy(['email' => $subscription->email]);
        if ($existingSubscription instanceof Subscription) {
            $existingError = $this->handleExistingSubscription($existingSubscription);

            if ($existingError) {
                return $this->jsonResponse(json_encode([
                    'success' => false,
                    'errors' => [$existingError],
                    'message' => 'Es sind Fehler aufgetreten. Bitte korrigiere die Eingaben.',
                ], JSON_THROW_ON_ERROR));
            }
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

        return $this->jsonResponse(json_encode([
            'success' => true,
            'errors' => [],
            'message' => 'Danke für die Anmeldung! Bitte bestätige deine E-Mail-Adresse über den Link, den wir dir gesendet haben.',
        ], JSON_THROW_ON_ERROR));
    }

    public function doubleOptInAction(string $token): ResponseInterface
    {
        $subscription = $this->doubleOptInService->processDoubleOptIn($token);
        $this->view->assign('subscription', $subscription);
        return $this->htmlResponse();
    }

    /**
     * @throws UnknownObjectException
     * @throws IllegalObjectTypeException
     * @throws \SodiumException
     */
    public function unsubscribeAction(string $token): ResponseInterface
    {
        $universalSecureTokenService = GeneralUtility::makeInstance(UniversalSecureTokenService::class);
        $email = $universalSecureTokenService->decrypt($token)['email'];
        $subscription = $this->subscriptionRepository->findOneBy(['email' => $email]);
        if ($subscription instanceof Subscription) {
            $subscription->optOutDate = new \DateTime();
            $subscription->status = SubscriptionStatusEnum::Unsubscribed;
            $this->subscriptionRepository->update($subscription);
        }
        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     */
    private function handleExistingSubscription(Subscription $existingSubscription): ?string
    {
        if ($existingSubscription->status->is(SubscriptionStatusEnum::Active)) {
            return 'Die Email-Adresse ist bereits eingetragen.';
        }

        $this->subscriptionRepository->remove($existingSubscription);
        return null;
    }

    private function validateSubscription(Subscription $subscription): array
    {
        $errors = [];

        if (! isset($subscription->email) || ($subscription->email === '' || $subscription->email === '0') || ! filter_var(
            $subscription->email,
            FILTER_VALIDATE_EMAIL,
        )) {
            $errors[] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        }

        if (! isset($subscription->firstName) || ($subscription->firstName === '' || $subscription->firstName === '0')) {
            $errors[] = 'Bitte einen Vornamen angeben.';
        }

        if (! isset($subscription->lastName) || ($subscription->lastName === '' || $subscription->lastName === '0')) {
            $errors[] = 'Bitte einen Nachnamen angeben.';
        }

        return $errors;
    }
}
