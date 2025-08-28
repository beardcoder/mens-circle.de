<?php

declare(strict_types=1);

/**
 * Configures PHP-CS-Fixer rules for TYPO3 coding standards.
 *
 * This configuration enables modern PHP syntax, PSR standards,
 * Symfony conventions, and strict typing while allowing risky fixes.
 * It applies these rules across all packages in the current directory.
 */
$config = TYPO3\CodingStandards\CsFixerConfig::create();

// Apply comprehensive rule sets for modern PHP and coding standards
$config->setRules([
    '@PHP84Migration' => true,
    '@PHP82Migration:risky' => true,
    '@PSR12' => true,
    '@PSR2' => true,
    '@PhpCsFixer' => true,
    '@Symfony' => true,
    '@Symfony:risky' => true,
    'simplified_if_return' => true,
    'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    'declare_strict_types' => true,
    'modernize_strpos' => true,
    'modernize_types_casting' => true,
    'use_arrow_functions' => true,
])
    ->setRiskyAllowed(true)
    ->getFinder()
    ->in(__DIR__ . '/packages');

return $config;
