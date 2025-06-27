<?php

rex_dir::delete(rex_path::plugin('yform', 'email'));
rex_dir::delete(rex_path::plugin('yform', 'rest'));
rex_dir::delete(rex_path::plugin('yform', 'docs'));
rex_dir::delete(rex_path::plugin('yform', 'manager'));
rex_dir::delete(rex_path::plugin('yform', 'tools'));


/** @var rex_addon $this */
$this->includeFile(__DIR__ . '/install.php');
