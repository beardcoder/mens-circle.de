<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Controller;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Model\FrontendUser;
use MensCircle\Sitepackage\Domain\Model\Participant;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use MensCircle\Sitepackage\Domain\Repository\ParticipantRepository;
use MensCircle\Sitepackage\PageTitle\EventPageTitleProvider;
use MensCircle\Sitepackage\Service\EmailService;
use MensCircle\Sitepackage\Service\FrontendUserService;
use Psr\Http\Message\ResponseInterface;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as CalendarEvent;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class EventController extends ActionController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ParticipantRepository $participantRepository,
        private readonly EventPageTitleProvider $eventPageTitleProvider,
        private readonly ImageService $imageService,
        private readonly PageRenderer $pageRenderer,
        private readonly MetaTagManagerRegistry $metaTagManagerRegistry,
        private readonly EmailService $emailService,
        private readonly FrontendUserService $frontendUserService,
        private readonly PersistenceManager $persistenceManager,
    ) {}

    public function listAction(): ResponseInterface
    {
        $this->view->assign('events', $this->eventRepository->findNextEvents());

        return $this->htmlResponse();
    }

    public function upcomingAction(?Event $event = null): ResponseInterface
    {
        $upcomingEvent = $event ?? $this->eventRepository->findNextUpcomingEvent();

        if ($upcomingEvent === null) {
            return $this->handleEventNotFoundError();
        }

        return $this->redirect(actionName: 'detail', arguments: [
            'event' => $upcomingEvent,
        ]);
    }

    public function detailAction(?Event $event = null, ?Participant $participant = null): ResponseInterface
    {
        $participantToAssign = $participant ?? GeneralUtility::makeInstance(Participant::class);

        if ($event === null) {
            return $this->handleEventNotFoundError();
        }

        $this->prepareSeoForEvent($event);

        $this->pageRenderer->addHeaderData($event->buildSchema($this->uriBuilder));

        $this->view->assign('event', $event);
        $this->view->assign('participant', $participantToAssign);

        return $this->htmlResponse();
    }

    public function initializeRegistrationAction(): void
    {
        $this->setRegistrationFieldValuesToArguments();
    }

    public function registrationAction(Participant $participant): ResponseInterface
    {
        $frontendUser = $this->frontendUserService->mapToFrontendUser($participant);
        $participant->setFeUser($frontendUser);
        $this->participantRepository->add($participant);
        $this->persistenceManager->persistAll();

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'registration.success',
                'sitepackage',
                [$participant->event->startDate->format('d.m.Y')],
            ),
        );

        $this->emailService->sendMail(
            'hallo@mens-circle.de',
            'MailToAdminOnRegistration',
            [
                'participant' => $participant,
            ],
            'Neue Anmeldung von ' . $participant->getName(),
            $this->request,
        );

        $redirectUrl = $this->uriBuilder->reset()
            ->setTargetPageUid(3)
            ->setNoCache(true)
            ->uriFor('detail', [
                'event' => $participant->event->getUid(),
            ]);

        return $this->redirectToUri($redirectUrl);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws PropagateResponseException
     */
    public function iCalAction(Event $event): \Psr\Http\Message\ResponseInterface
    {
        $processedFile = $this->imageService->applyProcessingInstructions(
            $event->getImage()?->getOriginalResource(),
            [
                'width' => '600c',
                'height' => '600c',
            ],
        );

        $dateTimeZone = new \DateTimeZone('Europe/Berlin');
        $startDateTime = new \DateTime($event->startDate->format('d.m.Y H:i'), $dateTimeZone);
        $endDateTime = new \DateTime($event->endDate->format('d.m.Y H:i'), $dateTimeZone);

        $calendarEvent = CalendarEvent::create()
            ->name($event->title)
            ->description($event->description)
            ->url($this->getUrlForEvent($event))
            ->image($this->imageService->getImageUri($processedFile, true))
            ->startsAt($startDateTime)
            ->endsAt($endDateTime)
            ->organizer('markus@letsbenow.de', 'Markus Sommer');

        if (
            $event->isOffline()
            && $event->location->latitude
            && $event->location->longitude
        ) {
            $calendarEvent
                ->address($event->getFullAddress(), $event->location->place)
                ->coordinates($event->location->latitude, $event->location->longitude);
        }

        $calendar = Calendar::create($event->getLongTitle())->event($calendarEvent);

        $response = $this->responseFactory->createResponse()
            ->withHeader('Cache-Control', 'private')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $event->getLongTitle() . '.ics"')
            ->withHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->withBody($this->streamFactory->createStream($calendar->get()));

        throw new PropagateResponseException($response, 200);
    }

    protected function setRegistrationFieldValuesToArguments(): void
    {
        $arguments = $this->request->getArguments();
        if (! isset($arguments['event'])) {
            return;
        }

        $event = $this->eventRepository->findByUid((int)$this->request->getArgument('event'));
        if (! $event instanceof Event) {
            return;
        }

        $registrationMvcArgument = $this->arguments->getArgument('participant');
        $mvcPropertyMappingConfiguration = $registrationMvcArgument->getPropertyMappingConfiguration();

        // Set event to registration (required for validation)
        $mvcPropertyMappingConfiguration->allowProperties('event');
        $mvcPropertyMappingConfiguration->allowCreationForSubProperty('event');
        $mvcPropertyMappingConfiguration->allowModificationForSubProperty('event');
        $arguments['participant']['event'] = (int)$this->request->getArgument('event');

        $this->request = $this->request->withArguments($arguments);
    }

    private function getUrlForEvent(Event $event): string
    {
        return $this->uriBuilder->reset()
            ->setCreateAbsoluteUri(true)
            ->setTargetPageUid(3)
            ->uriFor('detail', [
                'event' => $event->getUid(),
            ]);
    }

    private function mapParticipantToFeUser(Participant $participant): FrontendUser
    {
        /** @var FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);

        $frontendUser->setEmail($participant->getEmail());
        $frontendUser->setFirstName($participant->getFirstName());
        $frontendUser->setLastName($participant->getLastName());
        $frontendUser->setUsername($participant->getEmail());
        $frontendUser->setPassword(Uuid::v4()->toHex());

        return $frontendUser;
    }

    private function prepareSeoForEvent(Event $event): void
    {
        $this->eventPageTitleProvider->setTitle($event->getLongTitle());

        $processedFile = $this->imageService->applyProcessingInstructions($event->getImage()->getOriginalResource(), [
            'width' => '600c',
            'height' => '600c',
        ]);
        $imageUri = $this->imageService->getImageUri($processedFile, true);

        $this->setPageMetaProperty('og:title', $event->getLongTitle());
        $this->setPageMetaProperty('og:description', $event->description);
        $this->setPageMetaProperty('og:image', $imageUri, [
            'width' => 600,
            'height' => 600,
            'alt' => $event->getImage()
                ->getOriginalResource()
                ->getAlternative(),
        ]);
        $this->setPageMetaProperty('og:url', $this->getUrlForEvent($event));
    }

    private function setPageMetaProperty(string $property, string $value, array $additionalData = []): void
    {
        $this->metaTagManagerRegistry->getManagerForProperty($property)
            ->addProperty($property, $value, $additionalData);
    }

    private function handleEventNotFoundError(): ResponseInterface
    {
        $upcomingEvent = $this->eventRepository->findNextUpcomingEvent();
        if ($upcomingEvent === null) {
            $site = $this->request->getAttribute('site');
            assert($site instanceof Site);

            return $this->redirectToUri($site->getBase(), 301);
        }

        $this->addFlashMessage(LocalizationUtility::translate('event.not_found', 'sitepackage'));

        $redirectUrl = $this->uriBuilder->reset()
            ->setTargetPageUid(3)
            ->setNoCache(true)
            ->uriFor('detail', [
                'event' => $upcomingEvent,
            ]);

        return $this->redirectToUri($redirectUrl);
    }
}
