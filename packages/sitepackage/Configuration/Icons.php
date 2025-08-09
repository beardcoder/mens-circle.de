<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$iconDirectory = ExtensionManagementUtility::extPath('sitepackage', 'Resources/Public/Icons/');

$registeredIcons = [];

$svgFiles = new Finder()->files()->in($iconDirectory)->name('*.svg')->sortByName(true);

foreach ($svgFiles as $svgFile) {
    $iconIdentifier = $svgFile->getBasename('.svg');
    $iconSource = 'EXT:sitepackage/Resources/Public/Icons/' . $svgFile->getFilename();

    $registeredIcons[$iconIdentifier] = [
        'provider' => SvgIconProvider::class,
        'source' => $iconSource,
    ];
}

return $registeredIcons;
