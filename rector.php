<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/packages',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withPhpVersion(PhpVersion::PHP_85)
    ->withPhpSets(php85: true)
    ->withCodeQualityLevel(75)
    ->withAttributesSets(all: true)
    ->withCodingStyleLevel(100)
    ->withParallel()
    ->withSets([
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::TYPE_DECLARATION,
        SetList::NAMING,
        SetList::PHP_84,
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_13,
        LevelSetList::UP_TO_PHP_85,
    ])
    // To have a better analysis from PHPStan, we teach it here some more things
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH,
    ])
    ->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.1.0-8.5.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '13.2.0-13.6.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
    ])
    // If you use withImportNames(), you should consider excluding some TYPO3 files.
    ->withSkip([
        NameImportingPostRector::class => [
            'ClassAliasMap.php',
        ],
    ])
;
