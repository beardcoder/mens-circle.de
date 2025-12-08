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

// Define fancy ASCII header for package files
$header = <<<'HEADER'
Created by Markus Sommer
"Slow your breath, slow your mind — let the right code appear."
HEADER;

// Apply comprehensive rule sets for modern PHP and coding standards
$config->setRules([
    '@PHP84Migration' => true,
    '@PHP82Migration:risky' => true,
    '@PHP85Migration' => true,
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
    'header_comment' => [
        'header' => $header,
        'comment_type' => 'comment',
        'location' => 'after_declare_strict',
        'separate' => 'both',
    ],
])
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRiskyAllowed(true)
    ->getFinder()
    ->in(__DIR__ . '/packages');

return $config;
