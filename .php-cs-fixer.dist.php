<?php

use PhpCsFixer\Finder;
use Redaxo\PhpCsFixerConfig\Config;

$finder = new Finder()
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/pages'])
;

return Config::redaxo6()
    ->setFinder($finder)
;
