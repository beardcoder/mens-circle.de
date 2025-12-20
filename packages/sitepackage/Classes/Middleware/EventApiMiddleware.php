<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Domain\Model\Event;
use MensCircle\Sitepackage\Domain\Repository\EventRepository;
use MensCircle\Sitepackage\Service\ICalGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\StreamFactory;

/**
 * Middleware for generating iCal calendar files from events.
 *
 * Handles /api/event/{eventId}/ical endpoints.
 */
final readonly class EventApiMiddleware implements MiddlewareInterface
{
    private const string API_PATTERN = '#^/api/event/(\d+)/ical/?$#';

    private const string ORGANIZER_EMAIL = 'hallo@mens-circle.de';

    private const string ORGANIZER_NAME = 'Markus Sommer';

    public function __construct(
        private EventRepository $eventRepository,
        private ICalGenerator $iCalGenerator,
    ) {
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

        if (preg_match(self::API_PATTERN, $path, $matches) !== 1) {
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

        $iCalContent = $this->iCalGenerator->generate([
            'uid' => \sprintf('event-%d@mens-circle.de', $eventId),
            'summary' => $event->title,
            'description' => $event->description,
            'start' => $event->startDate,
            'end' => $event->endDate,
            'location' => $event->location->fullAddress,
            'geo' => [
                'lat' => $event->location->latitude,
                'lon' => $event->location->longitude,
            ],
            'organizer' => [
                'email' => self::ORGANIZER_EMAIL,
                'name' => self::ORGANIZER_NAME,
            ],
        ]);

        return $this->createICalResponse($iCalContent, $event);
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

    /**
     * Create the iCal response with proper headers.
     */
    private function createICalResponse(string $iCalContent, Event $event): ResponseInterface
    {
        $filename = $this->generateFilename($event);

        return new Response(
            body: new StreamFactory()->createStream($iCalContent),
            headers: [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => \sprintf('attachment; filename="%s.ics"', $filename),
            ]
        );
    }

    /**
     * Generate a URL-safe filename from event data.
     */
    private function generateFilename(Event $event): string
    {
        $raw = \sprintf('%s %s', $event->title, $event->startDate->format('d-m-Y'));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $raw) ?? $raw;

        return strtolower(trim($slug, '-'));
    }
}
