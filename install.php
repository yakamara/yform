<?php

/** @var rex_addon $this */

try {
    // transaction test.
    $testSQL = rex_sql::factory();
    $testSQL->beginTransaction();
    $testSQL->commit();
} catch (Exception $e) {
    throw new rex_sql_exception('db does not support transactions', $e);
}

foreach ($this->getInstalledPlugins() as $plugin) {
    // use path relative to __DIR__ to get correct path in update temp dir
    $file = __DIR__.'/plugins/'.$plugin->getName().'/install.php';

    if (file_exists($file)) {
        $plugin->includeFile($file);
    }
}
