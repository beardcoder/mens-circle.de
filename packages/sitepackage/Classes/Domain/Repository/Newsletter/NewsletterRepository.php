<?php
declare(strict_types=1);

namespace MensCircle\Sitepackage\Domain\Repository\Newsletter;

use MensCircle\Sitepackage\Domain\Repository\Traits\StoragePageAgnosticTrait;
use TYPO3\CMS\Extbase\Persistence\Repository;

class NewsletterRepository extends Repository
{
    use StoragePageAgnosticTrait;
}
