<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as CalendarEvent;
use TYPO3\CMS\Core\Http\UriFactory;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;

readonly class EventApiMiddleware implements MiddlewareInterface
{
    public const string BASE_PATH = '/api/event/';
    public const string PATH_ICAL = '/ical';

    public function __construct(
        private EventRepository $eventRepository,
        private StreamFactoryInterface $streamFactory,
        private string $basePath = self::BASE_PATH,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentPath = $request->getUri()->getPath();
        if (preg_match('#^' . preg_quote($this->basePath, '#') . '(\d+)' . preg_quote(self::PATH_ICAL, '#') . '$#', $currentPath, $matches)) {
            $eventId = (int)$matches[1];
            // You can now use $eventId as needed
            $response = $handler->handle($request);
            return $this->generateICalForEvent($request, $response, $eventId);
        }

        return $handler->handle($request);
    }

    public function generateICalForEvent(ServerRequestInterface $request, responseInterface $response, int $eventId): ResponseInterface
    {

        /** @var ?Event $event */
        $event = $this->eventRepository->findByUid($eventId);
        if (!$event instanceof Event) {
            throw new \RuntimeException('Event not found', 404);
        }
        $imageService = GeneralUtility::makeInstance(ImageService::class);
        $processedFile = $imageService->applyProcessingInstructions(
            $event->getImage()?->getOriginalResource(),
            [
                'width' => '600c',
                'height' => '600c',
            ],
        );

        $calendarEvent = CalendarEvent::create()
            ->name($event->title)
            ->description($event->description)
            ->url($this->getUrlForEvent($request, $event))
            ->image($imageService->getImageUri($processedFile, true))
            ->startsAt($event->startDate)
            ->endsAt($event->endDate)
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

        return $response
            ->withHeader('Cache-Control', 'private')
            ->withHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->withStatus(200)
            ->withHeader(
                'Content-Disposition',
                'attachment; filename="' . $event->getLongTitle() . '.ics"'
            )
            ->withBody($this->streamFactory->createStream($calendar->get()));
    }

    private function getUrlForEvent(ServerRequestInterface $request, Event $event): string
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setRequest($request);

        /** @var LinkFactory $linkFactory */
        $linkFactory = GeneralUtility::makeInstance(LinkFactory::class);
        $typolinkConfiguration['parameter'] = 3;
        $typolinkConfiguration['additionalParams'] = '&tx_sitepackage_eventdetail[action]=detail&tx_sitepackage_eventdetail[controller]=Event&tx_sitepackage_eventdetail[event]=' . $event->getUid();
        return $linkFactory->create('event', $typolinkConfiguration, $cObj)->getUrl();
    }
}
