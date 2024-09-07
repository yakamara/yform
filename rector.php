<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Transform\Rector\FuncCall\FuncCallToStaticCallRector;
use Rector\Transform\ValueObject\FuncCallToStaticCall;
use Rector\ValueObject\PhpVersion;
use Yakamara\YForm\Manager\Manager;

return RectorConfig::configure()
    ->withPaths(['docs', 'fragments', 'lib', 'module', 'pages', 'ytemplates', 'boot.php', 'tests'])
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
            'rex_yform_manager_table' => 'Yakamara\YForm\Manager\Table\Table',
            'rex_yform_manager_table_authorization' => 'Yakamara\YForm\Manager\Table\Authorization',
            'rex_yform_manager_table_api' => 'Yakamara\YForm\Manager\Table\Api',
            'rex_yform_manager_table_perm_edit' => 'Yakamara\YForm\Manager\Table\Perm\Edit',
            'rex_yform_manager_table_perm_view' => 'Yakamara\YForm\Manager\Table\Perm\View',
            'rex_yform_manager_collection' => 'Yakamara\YForm\Manager\Collection',
            'rex_yform_manager_dataset' => 'Yakamara\YForm\Manager\Dataset',
            'rex_yform_manager_field' => 'Yakamara\YForm\Manager\Field',
            'rex_yform_manager_manager' => 'Yakamara\YForm\Manager\Manager',
            'rex_yform_manager_query' => 'Yakamara\YForm\Manager\Query',
            'rex_yform_manager_search' => 'Yakamara\YForm\Manager\Search',
        ],
    )
    ->withConfiguredRule(FuncCallToStaticCallRector::class, [
        new FuncCallToStaticCall('rex_yform_manager_checkField', Manager::class, 'checkField'),
    ])
;
