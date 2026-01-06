<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Components;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Component\AbstractComponentCollection;
use TYPO3Fluid\Fluid\View\TemplatePaths;

final class ComponentCollection extends AbstractComponentCollection
{
    public function getTemplatePaths(): TemplatePaths
    {
        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths([
            GeneralUtility::getFileAbsFileName('EXT:sitepackage/Resources/Private/Components/'),
        ]);

        return $templatePaths;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdditionalVariables(string $viewHelperName): array
    {
        return [
            'isDevelopment' => Environment::getContext()->isDevelopment(),
        ];
    }

    protected function additionalArgumentsAllowed(string $viewHelperName): bool
    {
        return true;
    }
}
