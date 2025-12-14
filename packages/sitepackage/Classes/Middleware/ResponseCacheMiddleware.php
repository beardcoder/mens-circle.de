<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Service\CacheKeyGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\StreamFactory;

/**
 * PSR-15 Middleware for caching full HTTP responses.
 *
 * This middleware:
 * - Caches only successful GET requests without Extbase parameters
 * - Skips caching for logged-in frontend users
 * - Supports ETag-based conditional requests (304 Not Modified)
 * - Uses TYPO3's cache framework for storage
 * - Adds debug headers in development mode
 *
 * Similar to spatie/laravel-responsecache but tailored for TYPO3.
 */
final readonly class ResponseCacheMiddleware implements MiddlewareInterface
{
    private const string CACHE_IDENTIFIER = 'response_cache';

    private const int CACHE_LIFETIME = 3600; // 1 hour

    private FrontendInterface $cache;

    public function __construct(
        private CacheKeyGenerator $cacheKeyGenerator,
        private Context $context,
        CacheManager $cacheManager,
    ) {
        $this->cache = $cacheManager->getCache(self::CACHE_IDENTIFIER);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Only cache GET requests
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }

        // Skip caching if request should not be cached
        if (!$this->shouldCacheRequest($request)) {
            return $handler->handle($request);
        }

        // Generate cache key
        $cacheKey = $this->cacheKeyGenerator->generate($request);

        // Check for cached response
        $cachedData = $this->cache->get($cacheKey);

        if ($cachedData !== false && is_array($cachedData)) {
            return $this->createCachedResponse($request, $cachedData);
        }

        // Process request and cache response
        $response = $handler->handle($request);

        if ($this->shouldCacheResponse($response)) {
            $this->cacheResponse($cacheKey, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be cached.
     */
    private function shouldCacheRequest(ServerRequestInterface $request): bool
    {
        // Don't cache requests with Extbase parameters (forms, plugins, etc.)
        $queryParams = $request->getQueryParams();
        foreach (array_keys($queryParams) as $param) {
            if (is_string($param) && str_starts_with($param, 'tx_')) {
                return false;
            }
        }

        // Don't cache for logged-in frontend users
        try {
            if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false)) {
                return false;
            }
        } catch (\Exception) {
            // If context is not available, don't cache
            return false;
        }

        return true;
    }

    /**
     * Determine if the response should be cached.
     */
    private function shouldCacheResponse(ResponseInterface $response): bool
    {
        // Only cache successful responses
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Only cache HTML and JSON responses
        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType === '') {
            return false;
        }

        return str_contains($contentType, 'text/html') || str_contains($contentType, 'application/json');
    }

    /**
     * Cache the response.
     */
    private function cacheResponse(string $cacheKey, ResponseInterface $response): void
    {
        $body = (string) $response->getBody();
        $etag = $this->cacheKeyGenerator->generateETag($body);

        $data = [
            'body' => $body,
            'headers' => $response->getHeaders(),
            'status_code' => $response->getStatusCode(),
            'etag' => $etag,
            'cached_at' => time(),
        ];

        $this->cache->set($cacheKey, $data, [], self::CACHE_LIFETIME);
    }

    /**
     * Create a response from cached data.
     */
    private function createCachedResponse(ServerRequestInterface $request, array $cachedData): ResponseInterface
    {
        $etag = $cachedData['etag'] ?? null;

        // Handle conditional request (If-None-Match)
        if ($etag !== null && $this->hasMatchingETag($request, $etag)) {
            return new Response('php://temp', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=' . self::CACHE_LIFETIME,
            ]);
        }

        // Create full response from cache
        $headers = $cachedData['headers'] ?? [];

        // Add cache-related headers
        $headers['ETag'] = [$etag];
        $headers['Cache-Control'] = ['public, max-age=' . self::CACHE_LIFETIME];
        $headers['X-Response-Cache'] = ['HIT'];
        $headers['X-Response-Cache-Age'] = [(string) (time() - ($cachedData['cached_at'] ?? time()))];

        return new Response(
            body: (new StreamFactory())->createStream($cachedData['body'] ?? ''),
            status: $cachedData['status_code'] ?? 200,
            headers: $headers,
        );
    }

    /**
     * Check if the request has a matching ETag.
     */
    private function hasMatchingETag(ServerRequestInterface $request, string $etag): bool
    {
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');

        if ($ifNoneMatch === '') {
            return false;
        }

        // Support multiple ETags separated by comma
        $requestETags = array_map('trim', explode(',', $ifNoneMatch));

        return in_array($etag, $requestETags, true);
    }
}
