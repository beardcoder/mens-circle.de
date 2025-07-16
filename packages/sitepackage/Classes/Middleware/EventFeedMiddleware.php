<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Service\EventCalendarService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;

class EventFeedMiddleware implements MiddlewareInterface
{
    private const ROUTE_PATH = '/events/feed';

    private const FORMAT_JSON = 'json';
    private const FORMAT_ICS = 'ics';
    private const FORMAT_JCAL = 'jcal';

    private const CONTENT_TYPES = [
        self::FORMAT_JSON => 'application/json',
        self::FORMAT_ICS => 'text/calendar',
        self::FORMAT_JCAL => 'application/calendar+json',
    ];

    public function __construct(
        private readonly EventCalendarService $eventCalendarService
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($path !== self::ROUTE_PATH) {
            return $handler->handle($request);
        }

        $format = $this->determineFormat($request);
        $contentType = self::CONTENT_TYPES[$format];

        $feedContent = $this->eventCalendarService->getFeed($format);
        $etag = $this->eventCalendarService->getETag($format);

        // Check if client has matching ETag
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');
        if ($ifNoneMatch === '"' . $etag . '"') {
            return new Response('php://temp', 304, [
                'ETag' => '"' . $etag . '"',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        $stream = new Stream('php://temp', 'rw');
        $stream->write($feedContent);
        $stream->rewind();

        return new Response(
            $stream,
            200,
            [
                'Content-Type' => $contentType . '; charset=utf-8',
                'ETag' => '"' . $etag . '"',
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function determineFormat(ServerRequestInterface $request): string
    {
        $acceptHeader = $request->getHeaderLine('Accept');

        if (str_contains($acceptHeader, 'text/calendar')) {
            return self::FORMAT_ICS;
        }

        if (str_contains($acceptHeader, 'application/calendar+json')) {
            return self::FORMAT_JCAL;
        }

        // Default fallback to JSON
        return self::FORMAT_JSON;
    }
}
