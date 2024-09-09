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
            'rex_yform_choice_group_view' => 'Yakamara\YForm\Choice\GroupView',
            'rex_yform_choice_list' => 'Yakamara\YForm\Choice\ChoiceList',
            'rex_yform_choice_list_view' => 'Yakamara\YForm\Choice\ListView',
            'rex_yform_choice_view' => 'Yakamara\YForm\Choice\View',

            'rex_yform_base_abstract' => 'Yakamara\YForm\AbstractBase',

            'rex_yform_action_abstract' => 'Yakamara\YForm\Action\AbstractAction',
            'rex_yform_action_callback' => 'Yakamara\YForm\Action\Callback',
            'rex_yform_action_copy_value' => 'Yakamara\YForm\Action\CopyValue',
            'rex_yform_action_create_table' => 'Yakamara\YForm\Action\CreateTable',
            'rex_yform_action_db_query' => 'Yakamara\YForm\Action\DbQuery',
            'rex_yform_action_db' => 'Yakamara\YForm\Action\Db',
            'rex_yform_action_email' => 'Yakamara\YForm\Action\Email',
            'rex_yform_action_encrypt_value' => 'Yakamara\YForm\Action\EncryptValue',
            'rex_yform_action_html' => 'Yakamara\YForm\Action\Html',
            'rex_yform_action_manage_db' => 'Yakamara\YForm\Action\ManageDb',
            'rex_yform_action_php' => 'Yakamara\YForm\Action\Php',
            'rex_yform_action_readtable' => 'Yakamara\YForm\Action\ReadTable',
            'rex_yform_action_redirect' => 'Yakamara\YForm\Action\Redirect',
            'rex_yform_action_showtext' => 'Yakamara\YForm\Action\ShowText',
            'rex_yform_action_tpl2email' => 'Yakamara\YForm\Action\Tpl2Email',

            'rex_yform_validate_abstract' => 'Yakamara\YForm\Validate\AbstractValidate',
            'rex_yform_validate_compare_value' => 'Yakamara\YForm\Validate\CompareValue',
            'rex_yform_validate_compare' => 'Yakamara\YForm\Validate\Compare',
            'rex_yform_validate_customfunction' => 'Yakamara\YForm\Validate\CustomFunction',
            'rex_yform_validate_empty' => 'Yakamara\YForm\Validate\IsEmpty',
            'rex_yform_validate_in_names' => 'Yakamara\YForm\Validate\InNames',
            'rex_yform_validate_in_table' => 'Yakamara\YForm\Validate\InTable',
            'rex_yform_validate_intfromto' => 'Yakamara\YForm\Validate\IntFromTo',
            'rex_yform_validate_password_policy' => 'Yakamara\YForm\Validate\PasswordPolicy',
            'rex_yform_validate_preg_match' => 'Yakamara\YForm\Validate\PregMatch',
            'rex_yform_validate_size_range' => 'Yakamara\YForm\Validate\SizeRange',
            'rex_yform_validate_size' => 'Yakamara\YForm\Validate\Size',
            'rex_yform_validate_type' => 'Yakamara\YForm\Validate\Type',
            'rex_yform_validate_unique' => 'Yakamara\YForm\Validate\Unique',

            'rex_yform_value_abstract' => 'Yakamara\YForm\Value\AbstractValue',
            'rex_yform_value_article' => 'Yakamara\YForm\Value\Article',
            'rex_yform_value_be_link' => 'Yakamara\YForm\Value\BackendLink',
            'rex_yform_value_be_manager_relation' => 'Yakamara\YForm\Value\BackendManagerRelation',
            'rex_yform_value_be_media' => 'Yakamara\YForm\Value\BackendMedia',
            'rex_yform_value_be_table' => 'Yakamara\YForm\Value\BackendTable',
            'rex_yform_value_be_user' => 'Yakamara\YForm\Value\BackendUser',
            'rex_yform_value_checkbox' => 'Yakamara\YForm\Value\Checkbox',
            'rex_yform_value_choice' => 'Yakamara\YForm\Value\Choice',
            'rex_yform_value_csrf' => 'Yakamara\YForm\Value\Csrf',
            'rex_yform_value_date' => 'Yakamara\YForm\Value\Date',
            'rex_yform_value_datestamp' => 'Yakamara\YForm\Value\DateStamp',
            'rex_yform_value_datetime' => 'Yakamara\YForm\Value\DateTime',
            'rex_yform_value_email' => 'Yakamara\YForm\Value\Email',
            'rex_yform_value_emptyname' => 'Yakamara\YForm\Value\EmptyName',
            'rex_yform_value_fieldset' => 'Yakamara\YForm\Value\Fieldset',
            'rex_yform_value_generate_key' => 'Yakamara\YForm\Value\GenerateKey',
            'rex_yform_value_google_geocode' => 'Yakamara\YForm\Value\GoogleGeoCode',
            'rex_yform_value_hashvalue' => 'Yakamara\YForm\Value\HashValue',
            'rex_yform_value_hidden' => 'Yakamara\YForm\Value\Hidden',
            'rex_yform_value_html' => 'Yakamara\YForm\Value\Html',
            'rex_yform_value_index' => 'Yakamara\YForm\Value\Index',
            'rex_yform_value_integer' => 'Yakamara\YForm\Value\Integer',
            'rex_yform_value_ip' => 'Yakamara\YForm\Value\IP',
            'rex_yform_value_number' => 'Yakamara\YForm\Value\Number',
            'rex_yform_value_objparams' => 'Yakamara\YForm\Value\ObjParams',
            'rex_yform_value_password' => 'Yakamara\YForm\Value\Password',
            'rex_yform_value_php' => 'Yakamara\YForm\Value\Php',
            'rex_yform_value_prio' => 'Yakamara\YForm\Value\Prio',
            'rex_yform_value_resetbutton' => 'Yakamara\YForm\Value\ResetButton',
            'rex_yform_value_showvalue' => 'Yakamara\YForm\Value\ShowValue',
            'rex_yform_value_signature' => 'Yakamara\YForm\Value\Signature',
            'rex_yform_value_submit' => 'Yakamara\YForm\Value\Submit',
            'rex_yform_value_text' => 'Yakamara\YForm\Value\Text',
            'rex_yform_value_textarea' => 'Yakamara\YForm\Value\Textarea',
            'rex_yform_value_time' => 'Yakamara\YForm\Value\Time',
            'rex_yform_value_upload' => 'Yakamara\YForm\Value\Upload',
            'rex_yform_value_uuid' => 'Yakamara\YForm\Value\Uuid',

        ],
    )
    ->withConfiguredRule(FuncCallToStaticCallRector::class, [
        new FuncCallToStaticCall('rex_yform_manager_checkField', Manager::class, 'checkField'),
    ])
;
