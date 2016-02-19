<?php

rex_extension::register('OUTPUT_FILTER', function () {
    rex_dir::copy($this->getPath('data'),$this->getDataPath());
});


