<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Enum;

enum SubscriberStatus: int
{
    case Pending = 0;
    case Confirmed = 1;
    case Unsubscribed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Unsubscribed => 'Unsubscribed',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Confirmed;
    }
}
