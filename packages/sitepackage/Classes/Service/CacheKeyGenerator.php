<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Service;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Generates unique cache keys for HTTP requests.
 *
 * Creates deterministic cache keys based on:
 * - Request URI (path + query)
 * - Accept headers (for content negotiation)
 * - Accept-Language header (for i18n)
 */
final readonly class CacheKeyGenerator
{
    private const string CACHE_KEY_PREFIX = 'response_cache_';

    /**
     * Generate a unique cache key for the given request.
     */
    public function generate(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();

        // Build cache key components
        $components = [
            'uri' => $path . ($query !== '' ? '?' . $query : ''),
            'accept' => $request->getHeaderLine('Accept'),
            'accept_language' => $request->getHeaderLine('Accept-Language'),
        ];

        // Create hash from components
        $hash = hash('xxh128', serialize($components));

        return self::CACHE_KEY_PREFIX . $hash;
    }

    /**
     * Generate an ETag for the given content.
     */
    public function generateETag(string $content): string
    {
        return '"' . hash('xxh128', $content) . '"';
    }
}
