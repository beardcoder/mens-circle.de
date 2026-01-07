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

        $events = $this->eventRepository->findAllForBackend();
        $upcomingCount = $this->eventRepository->countUpcoming();
        $totalRegistrations = $this->eventRegistrationRepository->countAll();

        $moduleTemplate->assignMultiple([
            'events' => $events,
            'eventsCount' => $events->count(),
            'upcomingCount' => $upcomingCount,
            'totalRegistrations' => $totalRegistrations,
        ]);

        return $moduleTemplate->renderResponse('Backend/EventModule/Index');
    }

    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $events = $this->eventRepository->findAllForBackend();

        $moduleTemplate->assign('events', $events);

        return $moduleTemplate->renderResponse('Backend/EventModule/List');
    }

    public function showAction(Event $event): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $registrations = $this->eventRegistrationRepository->findByEvent($event);

        $moduleTemplate->assignMultiple([
            'event' => $event,
            'registrations' => $registrations,
            'confirmedCount' => $event->getConfirmedRegistrationsCount(),
            'availableSpots' => $event->getAvailableSpots(),
        ]);

        return $moduleTemplate->renderResponse('Backend/EventModule/Show');
    }

    public function togglePublishAction(Event $event): ResponseInterface
    {
        $event->setIsPublished(!$event->isPublished());
        $this->eventRepository->update($event);
        $this->persistenceManager->persistAll();

        $status = $event->isPublished() ? 'veröffentlicht' : 'als Entwurf gespeichert';
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
