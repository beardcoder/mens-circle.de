<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Location extends AbstractEntity
{
    public string $place;

    public string $address;

    public string $zip;

    public string $city;

    public float $longitude = 0.0;

    public float $latitude = 0.0;
}
