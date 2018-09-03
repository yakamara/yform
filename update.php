<?php

/**
 * @var rex_addon $this
 */

rex_dir::delete($this->getDataPath('fonts'));

foreach ($this->getInstalledPlugins() as $plugin) {
    // use path relative to __DIR__ to get correct path in update temp dir
    $file = __DIR__.'/plugins/'.$plugin->getName().'/install.php';

    if (file_exists($file)) {
        $plugin->includeFile($file);
    }
}

if ($this->getPlugin('manager')->isInstalled() && rex_string::versionCompare($this->getVersion(), '1.9', '<')) {
    $fields_removed = ['submits', 'uniqueform', 'select', 'select_sql', 'checkbox_sql', 'radio', 'radio_sql'];
    $fields_change = ['html', 'php', 'date', 'datetime', 'fieldset', 'time', 'upload', 'google_geocode', 'submit', 'be_medialist'];
    $actions_removed = ['fulltext_value', 'wrapper_value'];

    foreach ($fields_removed as $field) {
        rex_sql::factory()->setQuery('delete from '.rex_yform_manager_field::table().' where type_id="value" and type_name = ?', [$field]);
    }

    foreach ($fields_change as $field) {
        rex_sql::factory()->setQuery('delete from '.rex_yform_manager_field::table().' where type_id="value" and type_name = ?', [$field]);
    }

    foreach ($actions_removed as $action) {
        rex_sql::factory()->setQuery('delete from '.rex_yform_manager_field::table().' where type_id="action" and type_name = ?', [$action]);
    }
}

rex_autoload::removeCache();
