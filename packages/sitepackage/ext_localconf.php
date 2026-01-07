<?php

declare(strict_types=1);

use MensCircle\Sitepackage\Components\ComponentCollection;

defined('TYPO3') || die();

(static function (): void {
    // Register Fluid Component namespace globally
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['mc'] = [
        ComponentCollection::class,
    ];
})();
