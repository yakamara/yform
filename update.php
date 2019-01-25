<?php

/**
 * @var rex_addon $this
 */

rex_dir::delete($this->getDataPath('fonts'));

foreach ($this->getInstalledPlugins() as $plugin) {
    // use path relative to __DIR__ to get correct path in update temp dir
    $file = __DIR__ . '/plugins/' . $plugin->getName() . '/update.php';

    if (file_exists($file)) {
        $plugin->includeFile($file);
    }
}

if ($this->getPlugin('manager')->isInstalled() && rex_string::versionCompare($this->getVersion(), '3', '<')) {
    $fields_removed = [];
    $fields_renamed = ['labelexist' => 'in_names', 'existintable' => 'in_table'];

    foreach ($fields_renamed as $field_old_name => $field_new_name) {
        try {
            rex_sql::factory()->setQuery('update ' . rex::getTable('yform_field') . ' set type_name = ? where type_id="value" and type_name = ?', [$field_new_name, $field_old_name]);
            rex_sql::factory()->setQuery('update ' . rex::getTable('yform_history_field') . ' set field = ? where field = ?', [$field_new_name, $field_old_name]);
        } catch (rex_sql_exception $e) {
        }
    }

    foreach ($fields_removed as $field) {
        try {
            rex_sql::factory()->setQuery('delete from ' . rex_yform_manager_field::table() . ' where type_id="value" and type_name = ?', [$field]);
        } catch (rex_sql_exception $e) {
        }
    }

    try {
        rex_sql::factory()->setQuery('UPDATE ' . rex_yform_manager_field::table() . ' SET `format` = "" WHERE type_name = "datestamp" AND `format` = "mysql"');
    } catch (rex_sql_exception $e) {
    }
}

if ($this->getPlugin('manager')->isInstalled() && rex_string::versionCompare($this->getVersion(), '1.9', '<')) {
    $fields_removed = ['submits', 'uniqueform'];
    $fields_change = ['html', 'php', 'date', 'datetime', 'fieldset', 'time', 'upload', 'google_geocode', 'submit', 'be_medialist'];
    $actions_removed = ['fulltext_value', 'wrapper_value'];

    foreach ($fields_removed as $field) {
        try {
            rex_sql::factory()->setQuery('delete from ' . rex_yform_manager_field::table() . ' where type_id="value" and type_name = ?', [$field]);
        } catch (rex_sql_exception $e) {
        }
    }

    foreach ($fields_change as $field) {
        try {
            rex_sql::factory()->setQuery('delete from ' . rex_yform_manager_field::table() . ' where type_id="value" and type_name = ?', [$field]);
        } catch (rex_sql_exception $e) {
        }
    }

    foreach ($actions_removed as $action) {
        try {
            rex_sql::factory()->setQuery('delete from ' . rex_yform_manager_field::table() . ' where type_id="action" and type_name = ?', [$action]);
        } catch (rex_sql_exception $e) {
        }
    }
}

rex_autoload::removeCache();
