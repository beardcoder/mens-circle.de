<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Error\SentryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanStatus;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;

/**
 * PSR-15 Middleware for automatic Sentry performance tracing
 *
 * This middleware:
 * - Creates a transaction for each HTTP request
 * - Captures distributed tracing headers
 * - Records response status and timing
 * - Finishes the transaction on response
 */
final class SentryTracingMiddleware implements MiddlewareInterface
{
    private const SENTRY_TRACE_HEADER = 'sentry-trace';
    private const BAGGAGE_HEADER = 'baggage';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        SentryService::initialize();

        if (!SentryService::isInitialized()) {
            return $handler->handle($request);
        }

        $transaction = $this->startTransaction($request);

        if ($transaction === null) {
            return $handler->handle($request);
        }

        try {
            $response = $handler->handle($request);

            $this->finishTransaction($transaction, $response);

            // Add trace headers to response for distributed tracing
            return $this->addTraceHeaders($response, $transaction);
        } catch (\Throwable $exception) {
            $transaction->setStatus(SpanStatus::internalError());
            $transaction->finish();

            throw $exception;
        }
    }

    private function startTransaction(ServerRequestInterface $request): ?Transaction
    {
        $hub = SentrySdk::getCurrentHub();

        // Check for incoming trace headers (distributed tracing)
        $sentryTrace = $request->getHeaderLine(self::SENTRY_TRACE_HEADER);
        $baggage = $request->getHeaderLine(self::BAGGAGE_HEADER);

        $transactionContext = $sentryTrace !== ''
            ? TransactionContext::fromSentryTrace($sentryTrace, $baggage)
            : new TransactionContext();

        // Set transaction name based on route
        $transactionName = $this->getTransactionName($request);
        $transactionContext->setName($transactionName);
        $transactionContext->setOp('http.server');
        $transactionContext->setSource(TransactionSource::route());

        // Add request data
        $transactionContext->setData([
            'http.request.method' => $request->getMethod(),
            'url.path' => $request->getUri()->getPath(),
            'url.query' => $request->getUri()->getQuery(),
            'url.scheme' => $request->getUri()->getScheme(),
            'server.address' => $request->getUri()->getHost(),
            'user_agent.original' => $request->getHeaderLine('User-Agent'),
        ]);

        $transaction = $hub->startTransaction($transactionContext);
        $hub->setSpan($transaction);

        // Store transaction in request for access in controllers
        return $transaction;
    }

    private function finishTransaction(Transaction $transaction, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        // Set HTTP status
        $transaction->setHttpStatus($statusCode);

        // Set transaction status based on HTTP status
        $transaction->setStatus($this->mapHttpStatusToSpanStatus($statusCode));

        // Add response data
        $transaction->setData(array_merge(
            $transaction->getData(),
            [
                'http.response.status_code' => $statusCode,
                'http.response_content_length' => $response->getHeaderLine('Content-Length') ?: null,
            ],
        ));

        $transaction->finish();
    }

    private function addTraceHeaders(ResponseInterface $response, Transaction $transaction): ResponseInterface
    {
        $traceParent = $transaction->toTraceparent();
        $baggage = $transaction->toBaggage();

        if ($traceParent !== '') {
            $response = $response->withHeader(self::SENTRY_TRACE_HEADER, $traceParent);
        }

        if ($baggage !== '') {
            $response = $response->withHeader(self::BAGGAGE_HEADER, $baggage);
        }

        return $response;
    }

    private function getTransactionName(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Try to get route name from request attributes (set by TYPO3 routing)
        $route = $request->getAttribute('route');

        if ($route !== null && method_exists($route, 'getPath')) {
            return sprintf('%s %s', $method, $route->getPath());
        }

        // Normalize path for better grouping
        $normalizedPath = $this->normalizePath($path);

        return sprintf('%s %s', $method, $normalizedPath);
    }

    private function normalizePath(string $path): string
    {
        // Replace numeric IDs with placeholders for better transaction grouping
        $path = preg_replace('/\/\d+/', '/{id}', $path) ?? $path;

        // Replace UUIDs
        $path = preg_replace('/\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', '/{uuid}', $path) ?? $path;

        // Replace hashes (32+ hex chars)
        $path = preg_replace('/\/[a-f0-9]{32,}/i', '/{hash}', $path) ?? $path;

        return $path;
    }

    private function mapHttpStatusToSpanStatus(int $statusCode): SpanStatus
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => SpanStatus::ok(),
            $statusCode === 400 => SpanStatus::invalidArgument(),
            $statusCode === 401 => SpanStatus::unauthenticated(),
            $statusCode === 403 => SpanStatus::permissionDenied(),
            $statusCode === 404 => SpanStatus::notFound(),
            $statusCode === 409 => SpanStatus::aborted(),
            $statusCode === 429 => SpanStatus::resourceExhausted(),
            $statusCode === 499 => SpanStatus::cancelled(),
            $statusCode >= 500 && $statusCode < 600 => SpanStatus::internalError(),
            default => SpanStatus::unknownError(),
        };
    }
}
