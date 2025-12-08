<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

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
