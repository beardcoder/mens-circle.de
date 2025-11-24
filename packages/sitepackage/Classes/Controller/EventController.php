<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Controller;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Model\Participant;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use MensCircle\Sitepackage\Domain\Repository\ParticipantRepository;
use MensCircle\Sitepackage\Enum\ExtensionEnum;
use MensCircle\Sitepackage\Message\SendMailMessage;
use MensCircle\Sitepackage\Middleware\EventApiMiddleware;
use MensCircle\Sitepackage\PageTitle\EventPageTitleProvider;
use MensCircle\Sitepackage\Service\EmailService;
use MensCircle\Sitepackage\Service\FrontendUserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function listAction(): ResponseInterface
    {
        $this->view->assign('events', $this->eventRepository->findNextEvents());

        return $this->htmlResponse();
    }

    public function upcomingAction(?Event $event = null): ResponseInterface
    {
        $upcomingEvent = $event ?? $this->eventRepository->findNextUpcomingEvent()->getFirst();

        if (!$upcomingEvent instanceof Event) {
            return $this->handleEventNotFoundError();
        }

        return $this->redirect(
            actionName: 'detail',
            arguments: ['event' => $upcomingEvent]
        );
    }

    public function detailAction(
        ?Event $event = null,
        ?Participant $participant = null,
        ?bool $registrationComplete = false,
    ): ResponseInterface {
        $participantToAssign = $participant ?? GeneralUtility::makeInstance(Participant::class);

        if (!$event instanceof Event) {
            return $this->handleEventNotFoundError();
        }

        $this->prepareSeoForEvent($event);

        $this->pageRenderer->addHeaderData($event->buildSchema($this->uriBuilder));

        $this->view->assign('event', $event);
        $this->view->assign('registrationComplete', $registrationComplete);
        $this->view->assign('participant', $participantToAssign);

        return $this->htmlResponse();
    }

    public function registrationSuccessAction(Event $event): ResponseInterface
    {
        $this->view->assign('event', $event);

        return $this->htmlResponse();
    }

    public function initializeRegistrationAction(): void
    {
        $this->setRegistrationFieldValuesToArguments();
    }

    public function registrationAction(Participant $participant): ResponseInterface
    {
        try {
            $frontendUser = $this->frontendUserService->mapToFrontendUser($participant);
            $participant->setFeUser($frontendUser);
            $this->participantRepository->add($participant);
            $this->persistenceManager->persistAll();

            $this->messageBus->dispatch(
                new SendMailMessage(
                    'hallo@mens-circle.de',
                    'MailToAdminOnRegistration',
                    [
                        'participant' => $participant,
                    ],
                    'Neue Anmeldung von '.$participant->getName())
            );
        } catch (ExceptionInterface $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $this->redirect('registrationSuccess', null, null, ['event' => $participant->event]);
    }

    public function iCalAction(?Event $event = null): ResponseInterface
    {
        if (!$event instanceof Event) {
            return $this->handleEventNotFoundError();
        }

        $path = rtrim(EventApiMiddleware::BASE_PATH, '/').'/'.$event->getUid().rtrim(EventApiMiddleware::PATH_ICAL, '/').'/';
        $uri = $this->request->getUri()->withPath($path)->withQuery('')->withFragment('');

        return $this->redirectToUri((string) $uri);
    }

    protected function setRegistrationFieldValuesToArguments(): void
    {
        $arguments = $this->request->getArguments();
        if (!isset($arguments['event'])) {
            return;
        }

        $event = $this->eventRepository->findByUid((int) $this->request->getArgument('event'));
        if (!$event instanceof Event) {
            return;
        }

        $registrationMvcArgument = $this->arguments->getArgument('participant');
        $mvcPropertyMappingConfiguration = $registrationMvcArgument->getPropertyMappingConfiguration();

        // Set event to registration (required for validation)
        $mvcPropertyMappingConfiguration->allowProperties('event');
        $mvcPropertyMappingConfiguration->allowCreationForSubProperty('event');
        $mvcPropertyMappingConfiguration->allowModificationForSubProperty('event');
        $arguments['participant']['event'] = (int) $this->request->getArgument('event');

        $this->request = $this->request->withArguments($arguments);
    }

    private function getUrlForEvent(Event $event): string
    {
        return $this->uriBuilder->reset()
            ->setCreateAbsoluteUri(true)
            ->setTargetPageUid(3)
            ->uriFor('detail', [
                'event' => $event->getUid(),
            ])
        ;
    }

    private function prepareSeoForEvent(Event $event): void
    {
        $this->eventPageTitleProvider->setTitle($event->getLongTitle());

        $this->setPageMetaProperty('og:title', $event->getLongTitle());
        $this->setPageMetaProperty('og:description', $event->description);

        $imageUri = '';
        $imageRef = $event->getImage();

        if ($imageRef instanceof \TYPO3\CMS\Extbase\Domain\Model\FileReference) {
            $processedFile = $this->imageService->applyProcessingInstructions(
                $imageRef->getOriginalResource(),
                [
                    'width' => '600c',
                    'height' => '600c',
                ]
            );
            $imageUri = $this->imageService->getImageUri($processedFile, true);
        }

        if ($imageUri !== '') {
            $altText = $imageRef?->getOriginalResource()->getAlternative() ?? $event->title;
            $this->setPageMetaProperty('og:image', $imageUri, [
                'width' => 600,
                'height' => 600,
                'alt' => $altText,
            ]);
        }

        $this->setPageMetaProperty('og:url', $this->getUrlForEvent($event));
    }

    private function setPageMetaProperty(string $property, string $value, array $additionalData = []): void
    {
        $this->metaTagManagerRegistry->getManagerForProperty($property)->addProperty($property, $value, $additionalData);
    }

    private function handleEventNotFoundError(): ResponseInterface
    {
        $queryResult = $this->eventRepository->findNextUpcomingEvent();

        if (!$queryResult instanceof Event) {
            $site = $this->request->getAttribute('site');
            \assert($site instanceof Site);

            return $this->redirectToUri($site->getBase(), 301);
        }

        $this->addFlashMessage(LocalizationUtility::translate('event.not_found', ExtensionEnum::getName()));

        $redirectUrl = $this->uriBuilder->reset()
            ->setTargetPageUid(3)
            ->setNoCache(true)
            ->uriFor('detail', ['event' => $queryResult])
        ;

        return $this->redirectToUri($redirectUrl);
    }
}
