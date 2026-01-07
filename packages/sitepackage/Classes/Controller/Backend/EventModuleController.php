<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Controller\Backend;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRegistrationRepository;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Backend module controller for event management
 */
#[AsController]
final class EventModuleController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly EventRepository $eventRepository,
        protected readonly EventRegistrationRepository $eventRegistrationRepository,
        protected readonly PersistenceManagerInterface $persistenceManager,
        protected readonly UriBuilder $uriBuilder,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly ResponseFactory $responseFactory,
        protected readonly StreamFactory $streamFactory,
    ) {}

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Events Übersicht');

        $allEvents = $this->eventRepository->findAllForBackend();
        $upcomingEvents = $this->eventRepository->findUpcoming();
        $pastEvents = $this->eventRepository->findPast(5);

        // Calculate statistics
        $totalEvents = $allEvents->count();
        $upcomingCount = $upcomingEvents->count();
        $pastCount = $this->eventRepository->countPast();
        $totalRegistrations = $this->eventRegistrationRepository->countAll();

        // Find next event with available spots
        $nextEvent = null;
        $nextEventSpots = 0;
        foreach ($upcomingEvents as $event) {
            if (!$event->isPast()) {
                $nextEvent = $event;
                $nextEventSpots = $event->getAvailableSpots();
                break;
            }
        }

        $moduleTemplate->assignMultiple([
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'totalEvents' => $totalEvents,
            'upcomingCount' => $upcomingCount,
            'pastCount' => $pastCount,
            'totalRegistrations' => $totalRegistrations,
            'nextEvent' => $nextEvent,
            'nextEventSpots' => $nextEventSpots,
        ]);

        return $moduleTemplate->renderResponse('Backend/EventModule/Index');
    }

    public function listAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Alle Events');

        // Get filter from query parameters
        $queryParams = $request->getQueryParams();
        $filter = $queryParams['filter'] ?? 'all';

        // Filter events based on selection
        $events = match ($filter) {
            'upcoming' => $this->eventRepository->findUpcoming(),
            'past' => $this->eventRepository->findPast(),
            'published' => $this->eventRepository->findPublished(),
            default => $this->eventRepository->findAllForBackend(),
        };

        $moduleTemplate->assignMultiple([
            'events' => $events,
            'currentFilter' => $filter,
            'upcomingCount' => $this->eventRepository->countUpcoming(),
            'pastCount' => $this->eventRepository->countPast(),
            'totalCount' => $this->eventRepository->countAll(),
        ]);

        return $moduleTemplate->renderResponse('Backend/EventModule/List');
    }

    public function showAction(ServerRequestInterface $request): ResponseInterface
    {
        // Get event from query parameters
        $queryParams = $request->getQueryParams();
        $eventUid = (int)($queryParams['event'] ?? 0);

        $event = $this->eventRepository->findByUid($eventUid);

        if (!$event instanceof Event) {
            $this->addFlashMessage(
                'Event nicht gefunden.',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirectToRoute($request, 'list');
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('Event: ' . $event->getTitle());

        $registrations = $this->eventRegistrationRepository->findByEvent($event);
        $confirmedRegistrations = $this->eventRegistrationRepository->findConfirmedByEvent($event);

        $moduleTemplate->assignMultiple([
            'event' => $event,
            'registrations' => $registrations,
            'confirmedRegistrations' => $confirmedRegistrations,
            'registrationsCount' => $registrations->count(),
            'confirmedCount' => $confirmedRegistrations->count(),
            'availableSpots' => $event->getAvailableSpots(),
            'isFull' => $event->isFull(),
            'isPast' => $event->isPast(),
        ]);

        return $moduleTemplate->renderResponse('Backend/EventModule/Show');
    }

    public function togglePublishAction(ServerRequestInterface $request): ResponseInterface
    {
        // Get event from query parameters
        $queryParams = $request->getQueryParams();
        $eventUid = (int)($queryParams['event'] ?? 0);

        $event = $this->eventRepository->findByUid($eventUid);

        if (!$event instanceof Event) {
            $this->addFlashMessage(
                'Event nicht gefunden.',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirectToRoute($request, 'list');
        }

        $event->setIsPublished(!$event->getIsPublished());
        $this->eventRepository->update($event);
        $this->persistenceManager->persistAll();

        $status = $event->getIsPublished() ? 'veröffentlicht' : 'als Entwurf gespeichert';
        $this->addFlashMessage(
            sprintf('Event "%s" wurde %s.', $event->getTitle(), $status),
            ContextualFeedbackSeverity::OK,
        );

        return $this->redirectToRoute($request, 'list');
    }

    public function exportRegistrationsAction(ServerRequestInterface $request): ResponseInterface
    {
        // Get event from query parameters
        $queryParams = $request->getQueryParams();
        $eventUid = (int)($queryParams['event'] ?? 0);

        $event = $this->eventRepository->findByUid($eventUid);

        if (!$event instanceof Event) {
            $this->addFlashMessage(
                'Event nicht gefunden.',
                ContextualFeedbackSeverity::ERROR
            );
            return $this->redirectToRoute($request, 'list');
        }

        $registrations = $this->eventRegistrationRepository->findByEvent($event);

        $csvContent = "Vorname;Nachname;E-Mail;Telefon;Status;Angemeldet am\n";

        foreach ($registrations as $registration) {
            $csvContent .= sprintf(
                "%s;%s;%s;%s;%s\n",
                $registration->getFirstName(),
                $registration->getLastName(),
                $registration->getEmail(),
                $registration->getPhoneNumber(),
                $registration->getStatus(),
            );
        }

        $filename = sprintf(
            'anmeldungen-%s-%s.csv',
            $event->getSlug(),
            date('Y-m-d'),
        );

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withBody($this->streamFactory->createStream($csvContent));
    }

    /**
     * Add a flash message to the queue
     */
    protected function addFlashMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::INFO): void
    {
        $flashMessage = new FlashMessage(
            $message,
            '',
            $severity,
        );
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);
    }

    /**
     * Redirect to a route within the same module
     */
    protected function redirectToRoute(ServerRequestInterface $request, string $route, array $parameters = []): ResponseInterface
    {
        $routeIdentifier = $request->getAttribute('route');
        $moduleName = $routeIdentifier?->getOption('moduleName') ?? 'menscircle_events';

        $uri = $this->uriBuilder->buildUriFromRoute(
            $moduleName . '_' . $route,
            $parameters
        );

        return new RedirectResponse($uri);
    }
}
