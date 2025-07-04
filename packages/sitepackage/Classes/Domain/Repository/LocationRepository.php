<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class LocationRepository extends Repository
{
    use StoragePageAgnosticTrait;

    protected $defaultOrderings = [
        'place' => QueryInterface::ORDER_ASCENDING,
    ];
}
