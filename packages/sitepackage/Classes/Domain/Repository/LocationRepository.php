<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Repository;

use MensCircle\Sitepackage\Domain\Model\Location;
use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Location>
 */
class LocationRepository extends Repository
{
    use StoragePageAgnosticTrait;

    protected $defaultOrderings = [
        'place' => QueryInterface::ORDER_ASCENDING,
    ];
}
