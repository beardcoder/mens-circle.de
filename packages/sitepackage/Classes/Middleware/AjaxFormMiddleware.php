<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Middleware;

use MensCircle\Sitepackage\Service\FormHandlerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

final readonly class AjaxFormMiddleware implements MiddlewareInterface
{
    private const string API_PREFIX = '/api/form/';

    public function __construct(
        private FormHandlerService $formHandlerService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (!str_starts_with($path, self::API_PREFIX)) {
            return $handler->handle($request);
        }

        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(
                ['error' => 'Method not allowed'],
                405,
            );
        }

        $formType = substr($path, strlen(self::API_PREFIX));

        try {
            $result = $this->formHandlerService->handle($formType, $request);

            return new JsonResponse($result, $result['success'] ? 200 : 422);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                400,
            );
        } catch (\Throwable) {
            return new JsonResponse(
                ['error' => 'Internal server error'],
                500,
            );
        }
    }
}
