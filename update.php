<?php

/**
 * @var rex_addon $this
 */

rex_extension::register('OUTPUT_FILTER', function () {
    rex_dir::copy($this->getPath('data'), $this->getDataPath());
});

foreach ($this->getInstalledPlugins() as $plugin) {
    // use path relative to __DIR__ to get correct path in update temp dir
    $file = __DIR__.'/plugins/'.$plugin->getName().'/install.php';

    if (file_exists($file)) {
        $plugin->includeFile($file);
    }
}

if (rex_string::versionCompare($this->getVersion(), '1.9', '<')) {
    $fields_removed = ['submits'];
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
