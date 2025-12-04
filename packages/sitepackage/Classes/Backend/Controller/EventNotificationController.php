<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Backend\Controller;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Model\EventNotification;
use MensCircle\Sitepackage\Domain\Model\Participant;
use MensCircle\Sitepackage\Domain\Repository\EventNotificationRepository;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;

#[AsController]
class EventNotificationController extends ActionController
{
    private ModuleTemplate $moduleTemplate;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly EventNotificationRepository $eventNotificationRepository,
        private readonly MailerInterface $mailer,
        private readonly UriBuilder $backendUriBuilder,
        private readonly EventRepository $eventRepository,
        private readonly \TYPO3\CMS\Backend\Template\Components\ComponentFactory $componentFactory,
    ) {
    }

    public function prepareTemplate(ServerRequestInterface $serverRequest): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($serverRequest);
    }

    public function listAction(): ResponseInterface
    {
        $this->prepareTemplate($this->request);

        $this->moduleTemplate->setTitle('Select Event');
        $this->moduleTemplate->assign('events', $this->eventRepository->findAll());

        return $this->htmlResponse($this->moduleTemplate->render('EventNotification/List'));
    }

    public function newAction(Event $event, ?EventNotification $eventNotification = null): ResponseInterface
    {
        $this->prepareTemplate($this->request);
        $this->setDocHeader($this->request);
        $this->pageRenderer->loadJavaScriptModule('@mens-circle/sitepackage/bootstrap.js');
        $this->pageRenderer->addCssFile('EXT:sitepackage/Resources/Public/Backend/Styles/bootstrap.css');

        $this->moduleTemplate->assign('event', $event);
        $eventNotification ??= GeneralUtility::makeInstance(EventNotification::class);
        $eventNotification->event = $event;

        $this->moduleTemplate->assign('eventNotification', $eventNotification);
        $this->moduleTemplate->setTitle('Notification');

        return $this->htmlResponse($this->moduleTemplate->render('EventNotification/New'));
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws TransportExceptionInterface
     */
    public function sendAction(EventNotification $eventNotification): ResponseInterface
    {
        $event = $eventNotification->event;
        if (!$event instanceof Event) {
            throw new \RuntimeException('EventNotification must be associated with an Event.', 1646008234);
        }

        $eventNotification->setPid($event->getPid());
        $this->eventNotificationRepository->add($eventNotification);
        $objectStorage = $event->participants;

        $emailAddresses = array_map(
            static fn (Participant $participant): Address => new Address(
                $participant->email,
                $participant->name,
            ),
            $objectStorage->toArray(),
        );

        $fluidEmail = new FluidEmail();
        $fluidEmail
            ->bcc(...$emailAddresses)
            ->from(new Address('hallo@mens-circle.de', "Men's Circle Website"))
            ->subject($eventNotification->subject)
            ->format(FluidEmail::FORMAT_BOTH)
            ->to(new Address('hallo@mens-circle.de', "Men's Circle Website"))
            ->setTemplate('EventNotification')
            ->assign('subject', $eventNotification->subject)
            ->assign('message', $eventNotification->message)
        ;

        $this->mailer->send($fluidEmail);

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

        return $this->redirect('list');
    }

    protected function initializeModuleTemplate(ServerRequestInterface $serverRequest): ModuleTemplate
    {
        return $this->moduleTemplateFactory->create($serverRequest);
    }

    private function setDocHeader(ServerRequestInterface $serverRequest): void
    {
        $params = $serverRequest->getQueryParams();
        $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()
            ->getMenuRegistry()
        ;

        // Create a menu and set its identifier
        $menu = $this->componentFactory->createMenu();
        $menu->setIdentifier('select-event');

        // Fetch events
        $events = $this->eventRepository->findAll();
        if ($events->count() === 0) {
            return; // Exit early if no events exist
        }

        // Build menu items for each event
        foreach ($events as $event) {
            $menu->addMenuItem(
                $this->componentFactory->createMenuItem()
                    ->setTitle($event->longTitle)
                    ->setActive(isset($params['event']) && $event->getUid() === (int) $params['event'])
                    ->setHref((string) $this->backendUriBuilder->buildUriFromRoute(
                        'events_notification.EventNotification_new',
                        [
                            'event' => $event->getUid(),
                        ],
                    )),
            );
        }

        // Add menu to registry
        $menuRegistry->addMenu($menu);
    }
}
