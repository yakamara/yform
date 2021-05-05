<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$table = rex_sql_table::get(rex::getTable('yform_table'));
$hasMassDeletion = $table->hasColumn('mass_deletion');
$hasMassEdit = $table->hasColumn('mass_edit');

$table
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('description', 'text'))
    ->ensureColumn(new rex_sql_column('list_amount', 'int(11)', false, '50'))
    ->ensureColumn(new rex_sql_column('list_sortfield', 'varchar(191)', false, 'id'))
    ->ensureColumn(new rex_sql_column('list_sortorder', 'enum(\'ASC\',\'DESC\')', false, 'ASC'))
    ->ensureColumn(new rex_sql_column('prio', 'int(11)'))
    ->ensureColumn(new rex_sql_column('search', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('hidden', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('export', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('import', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('mass_deletion', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('mass_edit', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('schema_overwrite', 'tinyint(1)', false, 1))
    ->ensureColumn(new rex_sql_column('history', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('exclusive_view_roles', 'text'))
    ->ensureColumn(new rex_sql_column('exclusive_edit_roles', 'text'))
    ->ensureIndex(new rex_sql_index('table_name', ['table_name'], rex_sql_index::UNIQUE))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_field'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('prio', 'int(11)'))
    ->ensureColumn(new rex_sql_column('type_id', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('type_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('db_type', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('list_hidden', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('search', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('name', 'text'))
    ->ensureColumn(new rex_sql_column('label', 'text'))
    ->ensureColumn(new rex_sql_column('not_required', 'text'))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_history'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('dataset_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('action', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('user', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('timestamp', 'datetime'))
    ->ensureIndex(new rex_sql_index('dataset', ['table_name', 'dataset_id']))
    ->ensure();

rex_sql_table::get(rex::getTable('yform_history_field'))
    ->ensureColumn(new rex_sql_column('history_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('field', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('value', 'longtext'))
    ->setPrimaryKey(['history_id', 'field'])
    ->ensure();

if (!$hasMassDeletion) {
    rex_sql::factory()
        ->setTable(rex::getTable('yform_table'))
        ->setValue('mass_deletion', 1)
        ->update();
}

if (!$hasMassEdit) {
    rex_sql::factory()
        ->setTable(rex::getTable('yform_table'))
        ->setValue('mass_edit', 1)
        ->update();
}

$c = rex_sql::factory();
$c->setQuery('ALTER TABLE `' . rex::getTable('yform_table') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
$c->setQuery('ALTER TABLE `' . rex::getTable('yform_field') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
$c->setQuery('ALTER TABLE `' . rex::getTable('yform_history') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
$c->setQuery('ALTER TABLE `' . rex::getTable('yform_history_field') . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');

$addon = rex_addon::get('yform');
if ($addon->isInstalled() && rex_version::compare($addon->getVersion(), '3.5', '<')) {
    // Update of Userroles. Pull Permissions from Useraccount to YForm Table.
    $userRoles = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'user_role');
    $yformTables = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTablePrefix() . 'yform_table');

    $tables = [];
    foreach ($yformTables as $yformTable) {
        $tables[$yformTable['table_name']] = [
            'exclusive_view_roles' => ('' == $yformTable['exclusive_view_roles']) ? [] : explode(',', $yformTable['exclusive_view_roles']),
            'exclusive_edit_roles' => ('' == $yformTable['exclusive_edit_roles']) ? [] : explode(',', $yformTable['exclusive_edit_roles']),
        ];
    }

    foreach ($userRoles as $userRole) {
        $perms = json_decode($userRole['perms'], true);
        if (isset($perms['yform_manager_table']) && '' != $perms['yform_manager_table']) {
            if ('all' == $perms['yform_manager_table']) {
                foreach ($tables as $tableName => $table) {
                    $tables[$tableName]['exclusive_view_roles'][] = $userRole['id'];
                    $tables[$tableName]['exclusive_edit_roles'][] = $userRole['id'];
                }
            } else {
                $userTables = array_filter(explode('|', $perms['yform_manager_table']));
                foreach ($userTables as $userTable) {
                    if (array_key_exists($userTable, $tables)) {
                        $tables[$userTable]['exclusive_view_roles'][] = $userRole['id'];
                        $tables[$userTable]['exclusive_edit_roles'][] = $userRole['id'];
                    }
                }
            }
        }
    }

    foreach ($tables as $tableName => $table) {
        rex_sql::factory()->setDebug()->setQuery('UPDATE ' . rex::getTablePrefix() . 'yform_table set exclusive_view_roles=:exclusive_view_roles, exclusive_edit_roles=:exclusive_edit_roles where table_name=:table_name', [
            'exclusive_view_roles' => implode(',', array_unique($table['exclusive_view_roles'])),
            'exclusive_edit_roles' => implode(',', array_unique($table['exclusive_edit_roles'])),
            'table_name' => $tableName,
        ]);
    }
}

if (class_exists('rex_yform_manager_table')) {
    rex_yform_manager_table::deleteCache();
}
