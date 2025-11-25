<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ParticipantRepository extends Repository
{
    use StoragePageAgnosticTrait;

    protected $defaultOrderings = [
        'start_date' => QueryInterface::ORDER_ASCENDING,
    ];
}
