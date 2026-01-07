<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Controller\Backend;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRegistrationRepository;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Backend module controller for event management
 */
#[AsController]
final class EventModuleController extends ActionController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly EventRepository $eventRepository,
        protected readonly EventRegistrationRepository $eventRegistrationRepository,
        protected readonly PersistenceManagerInterface $persistenceManager,
    ) {}

    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
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

    public function listAction(string $filter = 'all'): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('Alle Events');

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

    public function showAction(Event $event): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
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

    public function togglePublishAction(Event $event): ResponseInterface
    {
        $event->setIsPublished(!$event->getIsPublished());
        $this->eventRepository->update($event);
        $this->persistenceManager->persistAll();

        $status = $event->getIsPublished() ? 'veröffentlicht' : 'als Entwurf gespeichert';
        $this->addFlashMessage(
            sprintf('Event "%s" wurde %s.', $event->getTitle(), $status),
            'Event aktualisiert',
            ContextualFeedbackSeverity::OK,
        );

        return $this->redirect('list');
    }

    public function exportRegistrationsAction(Event $event): ResponseInterface
    {
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
}
