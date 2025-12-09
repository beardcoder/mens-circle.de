<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registers custom page doktypes with allowed tables.
 */
(static function (): void {
    /** @var array<int> $customPageDoktypes $customPageDoktypes */
    $customPageDoktypes = [1724352539, 1724352571, 1724352888];
    $pageDoktypeRegistry = GeneralUtility::makeInstance(PageDoktypeRegistry::class);

    array_walk(
        $customPageDoktypes,
        static fn (int $doktype) => $pageDoktypeRegistry->add($doktype, ['allowedTables' => '*'])
    );
})();
