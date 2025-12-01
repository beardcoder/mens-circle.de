<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as CalendarEvent;
use Spatie\IcalendarGenerator\Enums\EventStatus as IcsEventStatus;
use Spatie\IcalendarGenerator\Properties\AppleLocationCoordinatesProperty;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

/**
 * Middleware for generating iCal calendar files from events.
 *
 * Handles /api/event/{eventId}/ical endpoints with proper HTTP caching via ETags.
 */
final readonly class EventApiMiddleware implements MiddlewareInterface
{
    private const string API_PATTERN = '#^/api/event/(\d+)/ical/?$#';
    private const string ORGANIZER_EMAIL = 'hallo@mens-circle.de';
    private const string ORGANIZER_NAME = 'Markus Sommer';
    private const int CACHE_MAX_AGE = 3600;
    private const int ALERT_MINUTES_SHORT = 15;
    private const int ALERT_MINUTES_LONG = 60;
    private const int IMAGE_SIZE = 600;
    private const int FILENAME_MAX_LENGTH = 120;
    private const int LOCATION_RADIUS_METERS = 72;

    public function __construct(
        private EventRepository $eventRepository,
        private ImageService $imageService,
        private LinkFactory $linkFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $eventId = $this->extractEventId($request);

        if ($eventId === null) {
            return $handler->handle($request);
        }

        return $this->generateICalForEvent($request, $eventId);
    }

    /**
     * Extract event ID from request path.
     */
    private function extractEventId(ServerRequestInterface $serverRequest): ?int
    {
        $path = $serverRequest->getUri()->getPath();

        if (preg_match(self::API_PATTERN, $path, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Generate iCal response for a specific event.
     */
    private function generateICalForEvent(ServerRequestInterface $serverRequest, int $eventId): ResponseInterface
    {
        $event = $this->eventRepository->findByUid($eventId);

        if (!$event instanceof Event) {
            return $this->createErrorResponse(404);
        }

        if (!$event->startDate instanceof \DateTimeInterface) {
            return $this->createErrorResponse(422);
        }

        // Handle conditional GET via ETag
        $etag = $this->buildEventEtag($event);
        if ($this->isNotModified($serverRequest, $etag)) {
            return $this->createNotModifiedResponse($etag);
        }

        // Generate calendar
        $calendarEvent = $this->buildCalendarEvent($serverRequest, $event);
        $calendar = Calendar::create($event->getLongTitle())->event($calendarEvent);

        return $this->createICalResponse($calendar->get(), $event->getLongTitle(), $etag);
    }

    /**
     * Create an error response with appropriate headers.
     */
    private function createErrorResponse(int $statusCode): ResponseInterface
    {
        return new Response('php://temp', $statusCode, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Check if the request should return 304 Not Modified.
     */
    private function isNotModified(ServerRequestInterface $serverRequest, string $etag): bool
    {
        return $serverRequest->getHeaderLine('If-None-Match') === '"'.$etag.'"';
    }

    /**
     * Create 304 Not Modified response.
     */
    private function createNotModifiedResponse(string $etag): ResponseInterface
    {
        return new Response('php://temp', 304, [
            'ETag' => '"'.$etag.'"',
            'Cache-Control' => 'public, max-age='.self::CACHE_MAX_AGE,
        ]);
    }

    /**
     * Build the calendar event with all properties.
     */
    private function buildCalendarEvent(ServerRequestInterface $serverRequest, Event $event): CalendarEvent
    {
        $calendarEvent = CalendarEvent::create()
            ->name($event->title)
            ->description($event->description)
            ->startsAt($event->startDate)
            ->organizer(self::ORGANIZER_EMAIL, self::ORGANIZER_NAME)
            ->uniqueIdentifier($this->buildEventUid($event))
            ->status($event->isCancelled() ? IcsEventStatus::Cancelled : IcsEventStatus::Confirmed)
        ;

        $this->addEventUrl($serverRequest, $event, $calendarEvent);
        $this->addEventDates($event, $calendarEvent);
        $this->addEventImage($event, $calendarEvent);
        $this->addEventLocation($event, $calendarEvent);
        $this->addEventAlerts($calendarEvent);

        return $calendarEvent;
    }

    /**
     * Add URL and conference information to the calendar event.
     */
    private function addEventUrl(ServerRequestInterface $serverRequest, Event $event, CalendarEvent $calendarEvent): void
    {
        $detailUrl = $this->getUrlForEvent($serverRequest, $event);
        $callUrl = trim($event->callUrl ?? '');

        if ($event->isOnline() && $callUrl !== '') {
            $calendarEvent->url($callUrl);
            $this->addConferenceHints($callUrl, $calendarEvent);
            $calendarEvent->attachment($callUrl, 'text/uri-list');
        } else {
            $calendarEvent->url($detailUrl);
        }
    }

    /**
     * Add conference hints for known platforms.
     */
    private function addConferenceHints(string $url, CalendarEvent $calendarEvent): void
    {
        if (str_contains($url, 'meet.google.com')) {
            $calendarEvent->googleConference($url);
        } elseif (str_contains($url, 'teams.microsoft.com')) {
            $calendarEvent->microsoftTeams($url);
        }
    }

    /**
     * Add creation and end dates to the calendar event.
     */
    private function addEventDates(Event $event, CalendarEvent $calendarEvent): void
    {
        if ($event->crdate instanceof \DateTimeInterface) {
            $calendarEvent->createdAt($event->crdate);
        }

        if ($event->endDate instanceof \DateTimeInterface) {
            $calendarEvent->endsAt($event->endDate);
        }
    }

    /**
     * Add event image if available.
     */
    private function addEventImage(Event $event, CalendarEvent $calendarEvent): void
    {
        $original = $event->getImage()?->getOriginalResource();

        if (!$original instanceof \TYPO3\CMS\Core\Resource\FileReference) {
            return;
        }

        $processedFile = $this->imageService->applyProcessingInstructions(
            $original,
            [
                'width' => self::IMAGE_SIZE.'c',
                'height' => self::IMAGE_SIZE.'c',
            ]
        );

        $calendarEvent->image($this->imageService->getImageUri($processedFile, true));
    }

    /**
     * Add location information for offline events.
     */
    private function addEventLocation(Event $event, CalendarEvent $calendarEvent): void
    {
        if (!$event->isOffline()) {
            return;
        }

        $calendarEvent->address($event->getFullAddress(), $event->location->place);

        $lat = $event->location->latitude;
        $lng = $event->location->longitude;

        if ($lat === 0.0 || $lng === 0.0) {
            return;
        }

        $calendarEvent->coordinates($lat, $lng);
        $calendarEvent->appendProperty(
            AppleLocationCoordinatesProperty::create(
                $lat,
                $lng,
                $event->getFullAddress(),
                $event->location->place,
                self::LOCATION_RADIUS_METERS
            )
        );
    }

    /**
     * Add reminder alerts to the calendar event.
     */
    private function addEventAlerts(CalendarEvent $calendarEvent): void
    {
        $calendarEvent->alertMinutesBefore(self::ALERT_MINUTES_SHORT, 'Event reminder');
        $calendarEvent->alertMinutesBefore(self::ALERT_MINUTES_LONG, 'Event starts in 1 hour');
    }

    /**
     * Create the iCal response with proper headers.
     */
    private function createICalResponse(string $ics, string $eventTitle, string $etag): ResponseInterface
    {
        $filename = $this->sanitizeFilename($eventTitle).'.ics';
        $disposition = \sprintf(
            'attachment; filename="%s"; filename*=UTF-8\'\'%s',
            $filename,
            rawurlencode($filename)
        );

        $headers = [
            'Cache-Control' => 'public, max-age='.self::CACHE_MAX_AGE,
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => $disposition,
            'ETag' => '"'.$etag.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Length' => (string) \strlen($ics),
        ];

        $stream = new Stream('php://temp', 'rw');
        $stream->write($ics);
        $stream->rewind();

        return new Response($stream, 200, $headers);
    }

    /**
     * Get the absolute URL for an event detail page.
     */
    private function getUrlForEvent(ServerRequestInterface $serverRequest, Event $event): string
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($serverRequest);

        $linkConfiguration = [
            'parameter' => 3,
            'additionalParams' => \sprintf(
                '&tx_sitepackage_eventdetail[action]=detail&tx_sitepackage_eventdetail[controller]=Event&tx_sitepackage_eventdetail[event]=%d',
                $event->getUid()
            ),
        ];

        try {
            return $this->linkFactory->create('event', $linkConfiguration, $contentObjectRenderer)->getUrl();
        } catch (UnableToLinkException) {
            $uri = $serverRequest->getUri()->withQuery('')->withFragment('')->withPath('/');

            return (string) $uri;
        }
    }

    /**
     * Build ETag for cache validation.
     */
    private function buildEventEtag(Event $event): string
    {
        $parts = [
            'id' => $event->getUid(),
            'title' => trim($event->title),
            'start' => $event->startDate?->format('c') ?? '',
            'end' => $event->endDate?->format('c') ?? '',
            'cancelled' => $event->isCancelled() ? '1' : '0',
            'attendance' => (string) $event->getRealAttendanceMode()->value,
            'crdate' => $event->crdate?->format('c') ?? '',
        ];

        return md5(json_encode($parts, \JSON_UNESCAPED_UNICODE) ?: trim($event->title));
    }

    /**
     * Sanitize a string to be safely used as a filename.
     */
    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\\/<>|:?*]+/', '-', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        $name = trim($name, ' -.');

        if ($name === '') {
            return 'event';
        }

        // Cap length to avoid extreme headers
        if (\function_exists('mb_strlen') && mb_strlen($name) > self::FILENAME_MAX_LENGTH) {
            return rtrim(mb_substr($name, 0, self::FILENAME_MAX_LENGTH), ' -.');
        }

        if (\strlen($name) > self::FILENAME_MAX_LENGTH * 1.5) {
            return rtrim(substr($name, 0, (int) (self::FILENAME_MAX_LENGTH * 1.5)), ' -.');
        }

        return $name;
    }

    /**
     * Build unique identifier for the calendar event.
     */
    private function buildEventUid(Event $event): string
    {
        $start = $event->startDate instanceof \DateTimeInterface ? $event->startDate->format('c') : '';

        return \sprintf('event-%d-%s@mens-circle.de', $event->getUid(), md5($start));
    }
}
