<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3Fluid\Fluid\Core\Component\AbstractComponentCollection;
use TYPO3Fluid\Fluid\View\TemplatePaths;

final class ComponentCollection extends AbstractComponentCollection
{
    public function getTemplatePaths(): TemplatePaths
    {
        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths([ExtensionManagementUtility::extPath('sitepackage', 'Resources/Private/Components')]);
        $templatePaths->setPartialRootPaths([ExtensionManagementUtility::extPath('sitepackage', 'Resources/Private/Partials')]);
        return $templatePaths;
    }
}
