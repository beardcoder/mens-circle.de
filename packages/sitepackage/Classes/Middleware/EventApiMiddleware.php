<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Middleware;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event as CalendarEvent;
use Eluceo\iCal\Domain\ValueObject\DateTime as CalendarDateTime;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\GeographicPosition;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use Nette\Utils\Strings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\StreamFactory;

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

    public function __construct(private EventRepository $eventRepository)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $eventId = $this->extractEventId($request);

        if ($eventId === null) {
            return $handler->handle($request);
        }

        return $this->generateICalResponse($eventId);
    }

    /**
     * Extract event ID from request path.
     */
    private function extractEventId(ServerRequestInterface $serverRequest): ?int
    {
        $path = $serverRequest->getUri()->getPath();

        if (!preg_match(self::API_PATTERN, $path, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * Generate iCal response for a specific event.
     */
    private function generateICalResponse(int $eventId): ResponseInterface
    {
        $event = $this->eventRepository->findByUid($eventId);

        if (!$event instanceof Event) {
            return $this->createErrorResponse(404);
        }

        if (!$event->startDate instanceof \DateTimeInterface) {
            return $this->createErrorResponse(422);
        }

        // Generate calendar
        $calendarEvent = $this->buildCalendarEvent($event);
        $calendar = new Calendar([$calendarEvent]);

        return $this->createICalResponse($calendar, $event);
    }

    /**
     * Create an error response with appropriate headers.
     */
    private function createErrorResponse(int $statusCode): Response
    {
        return new Response('php://temp', $statusCode, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function buildCalendarEvent(Event $event): CalendarEvent
    {
        $calendarEvent = new CalendarEvent();

        $calendarEvent
            ->setSummary($event->title)
            ->setDescription($event->description)
            ->setOccurrence(
                new TimeSpan(
                    new CalendarDateTime($event->startDate, false),
                    new CalendarDateTime($event->endDate, false)
                )
            )
            ->setOrganizer(
                new Organizer(
                    new EmailAddress(self::ORGANIZER_EMAIL),
                    self::ORGANIZER_NAME
                )
            )
            ->setLocation(
                new Location($event->location->fullAddress)
                    ->withGeographicPosition(
                        new GeographicPosition(
                            $event->location->latitude,
                            $event->location->longitude
                        )
                    )
            )
        ;

        return $calendarEvent;
    }

    /**
     * Create the iCal response with proper headers.
     */
    private function createICalResponse(Calendar $calendar, Event $event): ResponseInterface
    {
        $calendarFactory = new CalendarFactory();
        $calendarComponent = $calendarFactory->createCalendar($calendar);

        $filenameWithoutPrefix = Strings::webalize("{$event->title} {$event->startDate->format('d. m. Y')}");
        $headers = [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filenameWithoutPrefix}.ics\"",
        ];

        return new Response(
            body: new StreamFactory()->createStream((string) $calendarComponent),
            headers: $headers
        );
    }
}
