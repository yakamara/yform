<?php

rex_dir::delete(rex_path::plugin('yform', 'email'));
rex_dir::delete(rex_path::plugin('yform', 'rest'));

/** @var rex_addon $this */
$this->includeFile(__DIR__ . '/install.php');
