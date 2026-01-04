<?php

declare(strict_types=1);

// Development environment overrides
if (getenv('TYPO3_CONTEXT') === 'Development') {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] = E_ALL;

    // Disable caching for development
    foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $key => $_) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$key]['backend'] =
            \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
    }
}
