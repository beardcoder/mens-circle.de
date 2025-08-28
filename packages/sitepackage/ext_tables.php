<?php

declare(strict_types=1);

use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

(static function (): void {
    $customPageDoktypes = [1724352539, 1724352571, 1724352888];
    $dokTypeRegistry = GeneralUtility::makeInstance(PageDoktypeRegistry::class);

    array_walk(
        $customPageDoktypes,
        static fn (int $doktype) => $dokTypeRegistry->add($doktype, ['allowedTables' => '*'])
    );
})();
