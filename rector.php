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
            'rex_yform_rest' => 'Yakamara\YForm\Rest\Rest',
            'rex_yform_rest_route' => 'Yakamara\YForm\Rest\Route',
            'rex_yform_rest_auth_token' => 'Yakamara\YForm\Rest\AuthToken',
            'rex_yform_list' => 'Yakamara\YForm\List\YList',
            'rex_yform_list_tools' => 'Yakamara\YForm\List\Tools',
            'rex_var_yform_data' => 'Yakamara\YForm\RexVar\Data',
            'rex_var_yform_table_data' => 'Yakamara\YForm\RexVar\TableData',
            'rex_yform_email_template' => 'Yakamara\YForm\Email\Template',
        ],
    );
