<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Repository\Traits;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;

trait StoragePageAgnosticTrait
{
    public function initializeObject(): void
    {
        $typo3QuerySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $typo3QuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($typo3QuerySettings);
    }
}
