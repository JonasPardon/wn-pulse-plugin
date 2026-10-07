<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        // '@auto' => true,
        '@PSR12' => true,
        'no_whitespace_in_blank_line' => true,
        'blank_line_after_opening_tag' => false,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
        ],
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'single_quote' => true,
        'constant_case' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'native_function_casing' => true,
        'native_type_declaration_casing' => true,
        'no_useless_else' => true,
        'function_declaration' => true,
        'type_declaration_spaces' => true,
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
        'single_import_per_statement' => true,
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'clean_namespace' => true,
        // 'logical_operators' => true,
        'new_with_parentheses' => true,
        'ternary_to_null_coalescing' => true,
        'no_closing_tag' => true,
        'array_indentation' => true,
        'compact_nullable_type_declaration' => true,
        'no_spaces_around_offset' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=>' => 'single_space', // fails on aligned `=>`
                '=' => 'single_space',
            ],
        ],
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
            // 💡 root folder to check
            ->in(__DIR__)
            ->exclude('vendor')
            // 💡 additional files, eg bin entry file
            // ->append([__DIR__.'/bin-entry-file'])
            // 💡 folders to exclude, if any
            // ->exclude([/* ... */])
            // 💡 path patterns to exclude, if any
            // ->notPath([/* ... */])
            ->filter(function (SplFileInfo $file) {
                if (str_starts_with($file->getFilename(), '_')) {
                    // Ignore backend partials as the formatting is weird
                    return false;
                }

                return true;
            })
        // 💡 extra configs
        // ->ignoreDotFiles(false) // true by default in v3, false in v4 or future mode
        // ->ignoreVCS(true) // true by default
    )
;
