<?php

rex_yform::addTemplatePath(rex_path::addon('yform', 'ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    rex_view::addCssFile($this->getAssetsUrl('yform.css'));
    rex_view::addCssFile($this->getAssetsUrl('yform-formbuilder.css'));
    rex_view::addCssFile(rex_addon::get('yform')->getAssetsUrl('yform-docs.css'));

    rex_extension::register('PAGE_CHECKED', static function (rex_extension_point $ep) {
        $page = rex_be_controller::getPageObject('yform');
        $subpages = $page->getSubpages();
        if (!$subpages || 1 === count($subpages) && isset($subpages['manager'])) {
            $page->setHidden(true);
        }

    });
}


$userRoles = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'user_role');
$yformTables = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'yform_table');

$tables = [];
foreach($yformTables as $yformTable) {
    $tables[$yformTable['table_name']] = [
        'exclusive_view_roles' => ('' == $yformTable['exclusive_view_roles']) ? [] : explode(',', $yformTable['exclusive_view_roles']),
        'exclusive_edit_roles' => ('' == $yformTable['exclusive_edit_roles']) ? [] : explode(',', $yformTable['exclusive_edit_roles']),
    ];
}

dump($tables);

foreach($userRoles as $userRole) {
    $perms = json_decode($userRole['perms'], true);
    if (isset($perms['yform_manager_table']) && '' != $perms['yform_manager_table']) {
        if ($perms['yform_manager_table'] == 'all') {
            foreach($tables as $tableName => $table) {
                $tables[$tableName]['exclusive_view_roles'][] = $userRole['id'];
                $tables[$tableName]['exclusive_edit_roles'][] = $userRole['id'];
            }
        } else {
            $userTables = array_filter(explode('|', $perms['yform_manager_table']));
            foreach($userTables as $userTable) {
                if (array_key_exists($userTable, $tables)) {
                    $tables[$userTable]['exclusive_view_roles'][] = $userRole['id'];
                    $tables[$userTable]['exclusive_edit_roles'][] = $userRole['id'];
                }
            }
        }
    }
}

foreach($tables as $tableName => $table) {
    rex_sql::factory()->setDebug()->setQuery('UPDATE ' . rex::getTablePrefix() . 'yform_table set exclusive_view_roles=:exclusive_view_roles, exclusive_edit_roles=:exclusive_edit_roles where table_name=:table_name', [
        'exclusive_view_roles' => implode(',', array_unique($table['exclusive_view_roles'])),
        'exclusive_edit_roles' => implode(',', array_unique($table['exclusive_edit_roles'])),
        'table_name' => $tableName
    ]);
}

dump($tables);

/*
rex_user_role::get()
{"yform_manager_table":"all"}
    {"yform_manager_table":"|rex_kv30_historie|rex_ycom_group|rex_ynewsletter|rex_geo_caching_point|rex_ynewsletter_log|rex_yf_event|rex_yf_event_program|rex_yf_event_program2|"}
*/
