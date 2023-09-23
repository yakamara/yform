<?php

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

try {
    // transaction test.
    $testSQL = rex_sql::factory();
    $testSQL->transactional(static function () {
        rex_sql::factory()->setQuery('SELECT * rex_user LIMIT 1');
    });
} catch (Exception $e) {
    throw new rex_sql_exception('db does not support transactions', $e);
}

// old plugin docs still exists ? -> delete
$pluginDocs = __DIR__ . '/plugins/docs';
if (file_exists($pluginDocs)) {
    rex_dir::delete($pluginDocs);
}

foreach ($this->getInstalledPlugins() as $plugin) {
    // use path relative to __DIR__ to get correct path in update temp dir
    $file = __DIR__ . '/plugins/' . $plugin->getName() . '/install.php';

    if (file_exists($file)) {
        $plugin->includeFile($file);
    }
}

rex_dir::delete($this->getDataPath('fonts'));

rex_autoload::removeCache();
