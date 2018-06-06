<?php

/** @var rex_addon $this */

foreach ($this->getInstalledPlugins() as $plugin) {
    // use path relative to __DIR__ to get correct path in update temp dir
    $file = __DIR__.'/plugins/'.$plugin->getName().'/install.php';

    if (file_exists($file)) {
        $plugin->includeFile($file);
    }
}
