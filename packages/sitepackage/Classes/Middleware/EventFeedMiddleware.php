<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Service\EventCalendarService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;

class EventFeedMiddleware implements MiddlewareInterface
{
    private const ROUTE_BASE = '/events/feed';

    private const FORMATS = [
        'json' => 'application/json',
        'ics' => 'text/calendar',
        'jcal' => 'application/calendar+json',
    ];

    public function __construct(
        private readonly EventCalendarService $eventCalendarService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (!str_starts_with($path, self::ROUTE_BASE)) {
            return $handler->handle($request);
        }

        $format = $this->extractFormat($path, $request->getHeaderLine('Accept'));
        if (!$format) {
            return $handler->handle($request);
        }

        $feedContent = $this->eventCalendarService->getFeed($format);
        $etag = $this->eventCalendarService->getETag($format);

        // Handle conditional request
        if ($request->getHeaderLine('If-None-Match') === '"' . $etag . '"') {
            return $this->createResponse('', 304, $etag);
        }

        return $this->createResponse($feedContent, 200, $etag, self::FORMATS[$format]);
    }

    private function extractFormat(string $path, string $acceptHeader): ?string
    {
        // Check for file extension
        if (preg_match('#' . preg_quote(self::ROUTE_BASE) . '\.(\w+)$#', $path, $matches)) {
            $extension = $matches[1];
            return \array_key_exists($extension, self::FORMATS) ? $extension : null;
        }

        // Check for exact path match
        if ($path !== self::ROUTE_BASE) {
            return null;
        }

        // Determine format from Accept header
        return match (true) {
            str_contains($acceptHeader, 'text/calendar') => 'ics',
            str_contains($acceptHeader, 'application/calendar+json') => 'jcal',
            default => 'json',
        };
    }

    private function createResponse(string $content, int $status, string $etag, ?string $contentType = null): ResponseInterface
    {
        $headers = [
            'ETag' => '"' . $etag . '"',
            'Cache-Control' => 'public, max-age=3600',
        ];

        if ($contentType && $content) {
            $headers['Content-Type'] = $contentType . '; charset=utf-8';
            $headers['X-Content-Type-Options'] = 'nosniff';
        }

        if ($content) {
            $stream = new Stream('php://temp', 'rw');
            $stream->write($content);
            $stream->rewind();
            return new Response($stream, $status, $headers);
        }

        return new Response('php://temp', $status, $headers);
    }
}
