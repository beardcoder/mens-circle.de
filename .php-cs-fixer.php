<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/packages')
    ->exclude('node_modules')
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        '@PHP84Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try'],
        ],
        'cast_spaces' => ['space' => 'none'],
        'class_attributes_separation' => [
            'elements' => ['method' => 'one'],
        ],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'final_class' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'header_comment' => [
            'header' => <<<'EOF'
This file is part of the "sitepackage" extension for TYPO3 CMS.

For the full copyright and license information, please read the
LICENSE.txt file that was distributed with this source code.
EOF,
        ],
        'no_unused_imports' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_separation' => true,
        'phpdoc_summary' => false,
        'single_line_throw' => false,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'yoda_style' => ['equal' => false, 'identical' => false],
    ])
    ->setFinder($finder);
