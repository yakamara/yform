<?php

/**
 * @var rex_addon $this
 */

rex_extension::register('OUTPUT_FILTER', function () {
    rex_dir::copy($this->getPath('data'),$this->getDataPath());
});

if ($this->getPlugin('manager')->isInstalled()) {
    $this->getPlugin('manager')->includeFile('install.php');
}
