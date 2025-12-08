<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

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
