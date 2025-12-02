<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Enum;

enum SubscriptionStatusEnum: int
{
    case Pending = 1;

    case Active = 2;

    case Inactive = 3;

    case Unsubscribed = 4;

    case Expired = 5;

    public function is(self ...$statuses): bool
    {
        return \in_array($this, $statuses, true);
    }
}
