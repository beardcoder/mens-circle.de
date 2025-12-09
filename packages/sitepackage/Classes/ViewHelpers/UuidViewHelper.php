<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\ViewHelpers;

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class UuidViewHelper extends AbstractViewHelper
{
    #[\Override]
    public function render(): string
    {
        return StringUtility::getUniqueId('uuid_');
    }
}
