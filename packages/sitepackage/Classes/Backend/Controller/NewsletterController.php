<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Backend\Controller;

use MensCircle\Sitepackage\Domain\Model\Newsletter\Newsletter;
use MensCircle\Sitepackage\Domain\Model\Newsletter\Subscription;
use MensCircle\Sitepackage\Domain\Repository\Newsletter\NewsletterRepository;
use MensCircle\Sitepackage\Domain\Repository\Newsletter\SubscriptionRepository;
use MensCircle\Sitepackage\Enum\SubscriptionStatusEnum;
use MensCircle\Sitepackage\Service\UniversalSecureTokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;

#[AsController]
class NewsletterController extends ActionController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly MailerInterface $mailer,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly NewsletterRepository $newsletterRepository,
    ) {
    }

    /**
     * Generates the action menu.
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $view = $this->moduleTemplateFactory->create($request);

        $view->setFlashMessageQueue($this->getFlashMessageQueue());

        return $view;
    }

    public function newAction(?Newsletter $newsletter = null): ResponseInterface
    {
        $view = $this->initializeModuleTemplate($this->request);
        $this->pageRenderer->loadJavaScriptModule('@mens-circle/sitepackage/bootstrap.js');
        $this->pageRenderer->addCssFile('EXT:sitepackage/Resources/Public/Backend/Styles/bootstrap.css');
        $view->setTitle('Newsletter');

        $subscriptions = $this->subscriptionRepository->findBy([
            'status' => SubscriptionStatusEnum::Active,
        ]);
        $newsletter ??= GeneralUtility::makeInstance(Newsletter::class);

        $view->assign('newsletter', $newsletter);
        $view->assign('subscriptions', $subscriptions);

        return $view->renderResponse('Newsletter/New');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws IllegalObjectTypeException
     */
    public function sendAction(Newsletter $newsletter): ResponseInterface
    {
        $subscriptions = $this->subscriptionRepository->findBy([
            'status' => SubscriptionStatusEnum::Active,
        ])->toArray();

        array_walk(
            $subscriptions,
            static fn (Subscription $subscription) => $newsletter->addSubscription($subscription),
        );

        $emailAddresses = array_map(
            static fn (Subscription $subscription): Address => new Address(
                $subscription->email,
                $subscription->getName(),
            ),
            $subscriptions,
        );
        $this->newsletterRepository->add($newsletter);

        $universalSecureTokenService = GeneralUtility::makeInstance(UniversalSecureTokenService::class);

        foreach ($emailAddresses as $emailAddress) {
            $fluidEmail = new FluidEmail();
            $fluidEmail
                ->from(new Address('hallo@mens-circle.de', 'Men\'s Circle Website'))
                ->subject($newsletter->subject)
                ->format(FluidEmail::FORMAT_BOTH)
                ->to($emailAddress)
                ->setTemplate('Newsletter')
                ->setRequest($this->request)
                ->assign('subject', $newsletter->subject)
                ->assign(
                    'unsubscribeLink',
                    $this->generateFrontendLinkInBackendContext($universalSecureTokenService->encrypt([
                        'email' => $emailAddress->getAddress(),
                    ])),
                )
                ->assign('message', $newsletter->message)
            ;
            $this->mailer->send($fluidEmail);
        }

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            '',
            'Email Versendet',
            ContextualFeedbackSeverity::OK,
            true,
        );
        $flashMessageQueue->addMessage($flashMessage);

        return $this->redirect('new');
    }

    protected function generateFrontendLinkInBackendContext($token): string
    {
        // create url
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId(13);

        $parameters = [
            'tx_sitepackage_newsletter' => [
                'action' => 'unsubscribe',
                'controller' => 'Subscription',
                'token' => $token,
            ],
        ];

        return (string) $site->getRouter()
            ->generateUri(13, $parameters)
        ;
    }
}
