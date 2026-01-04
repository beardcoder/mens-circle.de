<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\Application;

(static function (): void {
    $classLoader = require dirname(__DIR__) . '/vendor/autoload.php';
    SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_FE);
    Application::createFromEnvironment()->run($classLoader);
})();
