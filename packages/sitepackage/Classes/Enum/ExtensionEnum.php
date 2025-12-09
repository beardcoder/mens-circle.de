<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Enum;

use TYPO3\CMS\Core\Utility\GeneralUtility;

enum ExtensionEnum: string
{
    case key = 'sitepackage';

    public static function getName(): string
    {
        return GeneralUtility::underscoredToUpperCamelCase(self::key->value);
    }
}
