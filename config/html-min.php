<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Html Min
    |--------------------------------------------------------------------------
    */
    'enable' => env('HTML_MINIFY', true),

    /*
    |--------------------------------------------------------------------------
    | Find DOCTYPE in document
    |--------------------------------------------------------------------------
    */
    'find_doctype_in_document' => true,

    /*
    |--------------------------------------------------------------------------
    | Remove whitespace between tags
    |--------------------------------------------------------------------------
    */
    'remove_whitespace_between_tags' => true,

    /*
    |--------------------------------------------------------------------------
    | Remove blank lines in script elements
    |--------------------------------------------------------------------------
    */
    'remove_blank_lines_in_script_elements' => false,
];
