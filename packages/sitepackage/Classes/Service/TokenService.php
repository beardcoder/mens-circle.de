<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Service;

final class TokenService
{
    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function generateShort(): string
    {
        return bin2hex(random_bytes(16));
    }
}
