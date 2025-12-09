<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

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

    public string $fullAddress {
        get => "{$this->address}, {$this->zip} {$this->city}";
    }
}
