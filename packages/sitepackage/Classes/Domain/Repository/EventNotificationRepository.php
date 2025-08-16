<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\Repository;

class EventNotificationRepository extends Repository
{
    use StoragePageAgnosticTrait;
}
