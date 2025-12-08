<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Repository\Newsletter;

use MensCircle\Sitepackage\Domain\Model\Newsletter\Newsletter;
use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Newsletter>
 */
class NewsletterRepository extends Repository
{
    use StoragePageAgnosticTrait;
}
