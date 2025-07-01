<?php

rex_dir::delete(rex_path::addon('yform','plugins'),true);

/** @var rex_addon $this */
$this->includeFile(__DIR__ . '/install.php');
