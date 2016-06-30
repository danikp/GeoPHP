<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__ . '/lib')
    ->in(__DIR__ . '/tests');

$header = <<<EOF
This file is part of the GeoPHP package.
Copyright (c) 2011 - 2016 Patrick Hayes and contributors

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

// `multiline_array_trailing_comma` is present in `symfony` fixer

return Symfony\CS\Config\Config::create()
    ->fixers([
        'psr1',
        'psr2',
        '-psr0',
        'symfony',
        'ordered_use', // order use statements
        '-short_array_syntax',
        'multiline_spaces_before_semicolon',
        'concat_with_spaces', // spaces
        'encoding',
        '-phpdoc_params', // do not align parameters in doc blocks
        '-align_double_arrow',
        '-align_equals',
        'header_comment',
    ])
    ->finder($finder);
