<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Error;

use TYPO3\CMS\Core\Error\ProductionExceptionHandler as CoreProductionExceptionHandler;

final class ProductionExceptionHandler extends CoreProductionExceptionHandler
{
    public function echoExceptionWeb(\Throwable $exception): void
    {
        $this->sendExceptionToSentry($exception);

        parent::echoExceptionWeb($exception);
    }

    public function echoExceptionCLI(\Throwable $exception): void
    {
        $this->sendExceptionToSentry($exception);

        parent::echoExceptionCLI($exception);
    }

    protected function sendExceptionToSentry(\Throwable $exception): void
    {
        SentryService::captureException($exception);
    }
}
