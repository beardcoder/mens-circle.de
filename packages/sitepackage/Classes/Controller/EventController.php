<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Controller;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Model\EventRegistration;
use MensCircle\Sitepackage\Domain\Model\FrontendUser;
use MensCircle\Sitepackage\Domain\Repository\EventRegistrationRepository;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use MensCircle\Sitepackage\Domain\Repository\FrontendUserRepository;
use MensCircle\Sitepackage\PageTitle\EventPageTitleProvider;
use PhpStaticAnalysis\Attributes\Throws;
use Psr\Http\Message\ResponseInterface;
use Spatie\SchemaOrg\EventStatusType;
use Spatie\SchemaOrg\Schema;
use Symfony\Component\Mime\Address;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerInterface;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class EventController extends ActionController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EventRegistrationRepository $eventRegistrationRepository,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly EventPageTitleProvider $eventPageTitleProvider,
        private readonly ImageService $imageService
    ) {}

    public function listAction(): ResponseInterface
    {
        $this->view->assign('events', $this->eventRepository->findNextEvents());

        return $this->htmlResponse();
    }

    public function detailAction(Event $event, ?EventRegistration $eventRegistration = null): ResponseInterface
    {
        $eventRegistrationToAssign = $eventRegistration ?? GeneralUtility::makeInstance(EventRegistration::class);
        $pageTitle = $event->title . ' am ' . $event->startDate->format('d.m.Y');
        $this->eventPageTitleProvider->setTitle($pageTitle);

        $processedFile = $this->imageService->applyProcessingInstructions(
            $event->getImage()->getOriginalResource(),
            ['width' => '600c', 'height' => '600c']
        );
        $imageUri = $this->imageService->getImageUri($processedFile, true);

        $metaTagManagerRegistry = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);
        assert($metaTagManagerRegistry instanceof MetaTagManagerRegistry);

        $metaTagManager = $metaTagManagerRegistry->getManagerForProperty('og:title');
        assert($metaTagManager instanceof MetaTagManagerInterface);

        $metaTagManager->addProperty('og:title', $pageTitle);
        $metaTagManager->addProperty('og:description', $event->description);
        $metaTagManager->addProperty('og:image', $imageUri);
        $metaTagManager->addProperty('og:image:width', '600');
        $metaTagManager->addProperty('og:image:height', '600');
        $metaTagManager->addProperty('og:image:alt', $event->getImage()->getOriginalResource()->getAlternative());

        $metaTagManager->addProperty('og:url', $this->uriBuilder->reset()->setCreateAbsoluteUri(true)->setTargetPageUid(3)->uriFor(
            'detail',
            [
                'event' => $event->getUid(),
            ],
        ));

        $this->buildSchema($event);
        $this->view->assign('event', $event);
        $this->view->assign('eventRegistration', $eventRegistrationToAssign);

        return $this->htmlResponse();
    }

    private function buildSchema(Event $event): void
    {
        $eventUrl = $this->uriBuilder->reset()->setCreateAbsoluteUri(true)->setTargetPageUid(3)->uriFor(
            'detail',
            [
                'event' => $event->getUid(),
            ],
        );

        $processedFile = $this->imageService->applyProcessingInstructions(
            $event->getImage()->getOriginalResource(),
            ['width' => '600c', 'height' => '600c']
        );
        $imageUri = $this->imageService->getImageUri($processedFile, true);
        $baseUrl = $this->uriBuilder->reset()->setCreateAbsoluteUri(true)->setTargetPageUid(1)->buildFrontendUri();
        $schema = Schema::event()
            ->name($event->title . ' am ' . $event->startDate->format('d.m.Y'))
            ->description($event->description)
            ->image($imageUri)
            ->startDate($event->startDate)
            ->endDate($event->endDate)
            ->eventAttendanceMode('https://schema.org/OfflineEventAttendanceMode')
            ->eventStatus(EventStatusType::EventRescheduled)
            ->location(
                Schema::place()
                    ->name($event->place)
                    ->address(
                        Schema::postalAddress()
                            ->streetAddress($event->address)
                            ->addressLocality($event->city)
                            ->postalCode($event->zip)
                            ->addressCountry('DE'),
                    ),
            )
            ->offers(
                Schema::offer()
                    ->validFrom($event->crdate)
                    ->price(0)
                    ->availability('https://schema.org/InStock')
                    ->url($eventUrl)
                    ->priceCurrency('EUR'),
            )
            ->organizer(Schema::person()->name('Markus Sommer')->url($baseUrl))
            ->performer(Schema::person()->name('Markus Sommer')->url($baseUrl));

        $this->getPageRenderer()->addHeaderData($schema->toScript());
    }

    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Set date format for field dateOfBirth.
     */
    public function initializeRegistrationAction(): void
    {
        $this->setRegistrationFieldValuesToArguments();
    }

    protected function setRegistrationFieldValuesToArguments(): void
    {
        $arguments = $this->request->getArguments();
        if (!isset($arguments['event'])) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->findByUid((int)$this->request->getArgument('event'));
        if (!$event instanceof Event) {
            return;
        }

        $registrationMvcArgument = $this->arguments->getArgument('eventRegistration');
        $mvcPropertyMappingConfiguration = $registrationMvcArgument->getPropertyMappingConfiguration();

        // Set event to registration (required for validation)
        $mvcPropertyMappingConfiguration->allowProperties('event');
        $mvcPropertyMappingConfiguration->allowCreationForSubProperty('event');
        $mvcPropertyMappingConfiguration->allowModificationForSubProperty('event');
        $arguments['eventRegistration']['event'] = (int)$this->request->getArgument('event');

        $this->request = $this->request->withArguments($arguments);
    }

    #[Throws('TransportExceptionInterface')]
    #[Throws('IllegalObjectTypeException')]
    public function registrationAction(EventRegistration $eventRegistration): ResponseInterface
    {
        $feUser = $this->frontendUserRepository->findOneBy(['email' => $eventRegistration->getEmail()]);
        if ($feUser === null) {
            $feUser = $this->mapRegistrationToFeUser($eventRegistration);
            $this->frontendUserRepository->add($feUser);
        }

        $eventRegistration->setFeUser($feUser);
        $this->eventRegistrationRepository->add($eventRegistration);

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'registration.success',
                'sitepackage',
                [$eventRegistration->event->startDate->format('d.m.Y')]
            )
        );

        $this->sendMailToAdminOnRegistration($eventRegistration);

        $redirectUrl = $this->uriBuilder->reset()->setTargetPageUid(3)->setNoCache(true)->uriFor(
            'detail',
            [
                'event' => $eventRegistration->event->getUid(),
            ],
        );

        return $this->redirectToUri($redirectUrl);
    }

    private function mapRegistrationToFeUser(EventRegistration $eventRegistration): FrontendUser
    {
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);
        assert($frontendUser instanceof FrontendUser);

        $frontendUser->setEmail($eventRegistration->getEmail());
        $frontendUser->setFirstName($eventRegistration->getFirstName());
        $frontendUser->setLastName($eventRegistration->getLastName());
        $frontendUser->setUsername($eventRegistration->getEmail());
        $frontendUser->setPassword(Uuid::v4()->toHex());

        return $frontendUser;
    }

    #[Throws('TransportExceptionInterface')]
    #[Throws('TransportExceptionInterface')]
    private function sendMailToAdminOnRegistration(EventRegistration $eventRegistration): void
    {
        $fluidEmail = new FluidEmail();
        $fluidEmail
            ->to('hallo@mens-circle.de')
            ->from(new Address('hallo@mens-circle.de', 'Men\'s Circle Website'))
            ->subject('Neue Anmeldung von' . $eventRegistration->getName())
            ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
            ->setTemplate('MailToAdminOnRegistration')
            ->assign('eventRegistration', $eventRegistration);
        $mailer = GeneralUtility::makeInstance(MailerInterface::class);
        assert($mailer instanceof MailerInterface);

        $mailer->send($fluidEmail);
    }
}
