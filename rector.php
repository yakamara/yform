<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths(['docs', 'fragments', 'lib', 'module', 'pages', 'ytemplates', 'boot.php'])
    ->withParallel()
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withConfiguredRule(
        RenameClassRector::class,
        [
            'rex_yform' => 'Yakamara\YForm\YForm',
        ],
    );
