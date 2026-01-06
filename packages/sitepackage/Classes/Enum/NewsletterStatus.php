<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Enum;

enum NewsletterStatus: int
{
    case Draft = 0;
    case Scheduled = 1;
    case Sending = 2;
    case Sent = 3;
    case Failed = 4;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::Draft;
    }

    public function canSend(): bool
    {
        return $this === self::Draft || $this === self::Failed;
    }
}
