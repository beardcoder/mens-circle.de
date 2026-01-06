<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Service\SubscriptionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

final readonly class NewsletterMiddleware implements MiddlewareInterface
{
    private const ROUTES = [
        '/newsletter/subscribe' => 'subscribe',
        '/newsletter/confirm' => 'confirm',
        '/newsletter/unsubscribe' => 'unsubscribe',
    ];

    public function __construct(
        private SubscriptionService $subscriptionService,
        private ViewFactoryInterface $viewFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (!isset(self::ROUTES[$path])) {
            return $handler->handle($request);
        }

        $method = self::ROUTES[$path];

        return $this->$method($request);
    }

    private function subscribe(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(['error' => 'Method not allowed'], 405);
        }

        $body = $this->parseBody($request);
        $email = $body['email'] ?? '';

        $result = $this->subscriptionService->subscribe($email);

        if ($this->wantsJson($request)) {
            return new JsonResponse($result, $result['success'] ? 200 : 422);
        }

        return $this->renderPage('Subscribe', $result);
    }

    private function confirm(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $token = $params['token'] ?? '';

        if ($token === '') {
            return $this->renderPage('Confirm', [
                'success' => false,
                'message' => 'Invalid confirmation link.',
            ]);
        }

        $result = $this->subscriptionService->confirm($token);

        return $this->renderPage('Confirm', $result);
    }

    private function unsubscribe(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $email = $params['email'] ?? '';
        $token = $params['token'] ?? '';

        if ($request->getMethod() === 'GET') {
            // Show unsubscribe confirmation page
            return $this->renderPage('Unsubscribe', [
                'email' => $email,
                'token' => $token,
                'showForm' => true,
            ]);
        }

        // Process unsubscribe
        $body = $this->parseBody($request);
        $email = $body['email'] ?? $email;
        $token = $body['token'] ?? $token;

        $result = $this->subscriptionService->unsubscribe($email, $token);

        if ($this->wantsJson($request)) {
            return new JsonResponse($result, $result['success'] ? 200 : 422);
        }

        return $this->renderPage('Unsubscribe', array_merge($result, ['showForm' => false]));
    }

    private function renderPage(string $template, array $variables): HtmlResponse
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:sitepackage/Resources/Private/Templates/Newsletter/'],
            layoutRootPaths: ['EXT:sitepackage/Resources/Private/PageView/Layouts/'],
            partialRootPaths: ['EXT:sitepackage/Resources/Private/Components/'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple($variables);

        return new HtmlResponse($view->render($template));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseBody(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $body = (string)$request->getBody();

        if (str_contains($contentType, 'application/json')) {
            return json_decode($body, true) ?: [];
        }

        parse_str($body, $data);

        return $data;
    }

    private function wantsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');

        return str_contains($accept, 'application/json');
    }
}
