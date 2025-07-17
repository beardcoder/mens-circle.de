<?php

declare(strict_types=1);

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
