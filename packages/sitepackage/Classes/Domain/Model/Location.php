<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Model;

use MensCircle\Sitepackage\Enum\EventAttendanceModeEnum;
use MensCircle\Sitepackage\Enum\EventStatusEnum;
use Spatie\SchemaOrg\Event as EventSchema;
use Spatie\SchemaOrg\ItemAvailability;
use Spatie\SchemaOrg\Schema;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Service\ImageService;

class Location extends AbstractEntity
{
    public string $place;

    public string $address;

    public string $zip;

    public string $city;

    public float $longitude = 0.0;

    public float $latitude = 0.0;
}
