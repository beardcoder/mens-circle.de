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

readonly class EventApiMiddleware implements MiddlewareInterface
{
    public const string BASE_PATH = '/api/event/';
    public const string PATH_ICAL = '/ical';

    private const string ORGANIZER_EMAIL = 'hallo@mens-circle.de';
    private const string ORGANIZER_NAME = 'Markus Sommer';

    public function __construct(
        private EventRepository $eventRepository,
        private ImageService $imageService,
        private LinkFactory $linkFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (preg_match('#^' . preg_quote(self::BASE_PATH, '#') . '([0-9]+)' . preg_quote(self::PATH_ICAL, '#') . '/?$#', $path, $m)) {
            $eventId = (int)$m[1];
            return $this->generateICalForEvent($request, $eventId);
        }

        return $handler->handle($request);
    }

    private function generateICalForEvent(ServerRequestInterface $request, int $eventId): ResponseInterface
    {
        $event = $this->eventRepository->findByUid($eventId);
        if (! $event instanceof Event) {
            return new Response('php://temp', 404, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Cache-Control' => 'no-store',
            ]);
        }

        // Validate required data
        if (! $event->startDate instanceof \DateTimeInterface) {
            return new Response('php://temp', 422, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Cache-Control' => 'no-store',
            ]);
        }

        // Conditional GET via ETag
        $etag = $this->buildEventEtag($event);
        if ($request->getHeaderLine('If-None-Match') === '"' . $etag . '"') {
            return new Response('php://temp', 304, [
                'ETag' => '"' . $etag . '"',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        // Build the calendar event
        $calendarEvent = CalendarEvent::create()
            ->name($event->title)
            ->description($event->description)
            ->startsAt($event->startDate)
            ->organizer(self::ORGANIZER_EMAIL, self::ORGANIZER_NAME)
            ->uniqueIdentifier($this->buildEventUid($event))
            ->status($event->isCancelled() ? IcsEventStatus::Cancelled : IcsEventStatus::Confirmed);

        // Set URL: prefer online call URL for online events, else detail URL
        $detailUrl = $this->getUrlForEvent($request, $event);
        $callUrl = trim($event->callUrl ?? '');
        if ($event->isOnline() && $callUrl !== '') {
            $calendarEvent->url($callUrl);
            // Conference hints for Apple/clients
            if (str_contains($callUrl, 'meet.google.com')) {
                $calendarEvent->googleConference($callUrl);
            } elseif (str_contains($callUrl, 'teams.microsoft.com')) {
                $calendarEvent->microsoftTeams($callUrl);
            }
            // Also add as attachment for broader client support
            $calendarEvent->attachment($callUrl, 'text/uri-list');
        } else {
            $calendarEvent->url($detailUrl);
        }

        if ($event->crdate instanceof \DateTimeInterface) {
            $calendarEvent->createdAt($event->crdate);
        }

        if ($event->endDate instanceof \DateTimeInterface) {
            $calendarEvent->endsAt($event->endDate);
        }

        // Optional image if present
        $original = $event->getImage()?->getOriginalResource();
        if ($original !== null) {
            $processedFile = $this->imageService->applyProcessingInstructions(
                $original,
                [
                    'width' => '600c',
                    'height' => '600c',
                ],
            );
            $calendarEvent->image($this->imageService->getImageUri($processedFile, true));
        }

        if ($event->isOffline()) {
            $calendarEvent->address($event->getFullAddress(), $event->location->place);
            $lat = (float)$event->location->latitude;
            $lng = (float)$event->location->longitude;
            if ($lat !== 0.0 && $lng !== 0.0) {
                $calendarEvent->coordinates($lat, $lng);
                // Add Apple structured location for iOS/macOS Maps integration
                $calendarEvent->appendProperty(
                    AppleLocationCoordinatesProperty::create(
                        $lat,
                        $lng,
                        $event->getFullAddress(),
                        $event->location->place,
                        72, // default radius in meters
                    ),
                );
            }
        }

        // Add helpful alerts
        $calendarEvent->alertMinutesBefore(15, 'Event reminder');
        $calendarEvent->alertMinutesBefore(60, 'Event starts in 1 hour');

        $calendar = Calendar::create($event->getLongTitle())->event($calendarEvent);
        $ics = $calendar->get();

        // Prepare response
        $filename = $this->sanitizeFilename($event->getLongTitle()) . '.ics';
        $disposition = 'attachment; filename="' . $filename . '"; filename*=' . "UTF-8''" . rawurlencode($filename);
        $headers = [
            'Cache-Control' => 'public, max-age=3600',
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => $disposition,
            'ETag' => '"' . $etag . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Length' => (string)strlen($ics),
        ];

        $stream = new Stream('php://temp', 'rw');
        $stream->write($ics);
        $stream->rewind();

        return new Response($stream, 200, $headers);
    }

    private function getUrlForEvent(ServerRequestInterface $request, Event $event): string
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setRequest($request);

        $linkConfiguration = [
            'parameter' => 3,
            'additionalParams' => '&tx_sitepackage_eventdetail[action]=detail&tx_sitepackage_eventdetail[controller]=Event&tx_sitepackage_eventdetail[event]=' . $event->getUid(),
        ];

        try {
            return $this->linkFactory->create('event', $linkConfiguration, $cObj)->getUrl();
        } catch (UnableToLinkException) {
            $uri = $request->getUri()->withQuery('')->withFragment('')->withPath('/');
            return (string)$uri;
        }
    }

    private function buildEventEtag(Event $event): string
    {
        $parts = [
            'id' => $event->getUid(),
            'title' => trim($event->title),
            'start' => $event->startDate?->format('c') ?? '',
            'end' => $event->endDate?->format('c') ?? '',
            'cancelled' => $event->isCancelled() ? '1' : '0',
            'attendance' => (string)$event->getRealAttendanceMode()->value,
            'crdate' => $event->crdate?->format('c') ?? '',
        ];

        return md5(json_encode($parts, JSON_UNESCAPED_UNICODE));
    }

    private function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\\/<>|:?*]+/', '-', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        $name = trim($name, ' -.');

        if ($name === '') {
            $name = 'event';
        }

        // Optionally cap length to avoid extreme headers; keep multibyte safety when available
        if (function_exists('mb_strlen') && mb_strlen($name) > 120) {
            $name = rtrim(mb_substr($name, 0, 120), ' -.');
        } elseif (strlen($name) > 180) { // byte-based fallback
            $name = rtrim(substr($name, 0, 180), ' -.');
        }

        return $name;
    }

    private function buildEventUid(Event $event): string
    {
        $start = $event->startDate instanceof \DateTimeInterface ? $event->startDate->format('c') : '';
        return sprintf('event-%d-%s@mens-circle.de', $event->getUid(), md5($start));
    }
}
