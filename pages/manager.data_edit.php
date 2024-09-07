<?php

use Yakamara\YForm\Manager\Manager;
use Yakamara\YForm\Manager\Table\Table;

$table_name = rex_request('table_name', 'string');
$table = Table::get($table_name);

if ($table && $table->isGranted('VIEW', rex::getUser())) {
    try {
        $page = new Manager();
        $page->setTable($table);
        $page->setLinkVars(['page' => rex_be_controller::getCurrentPage(), 'table_name' => $table->getTableName()]);
        echo $page->getDataPage();
    } catch (Exception $e) {
        echo rex_view::warning(nl2br($e->getMessage() . "\n" . $e->getTraceAsString()));
    }
} else {
    if (!$table) {
        echo rex_view::warning(rex_i18n::msg('yform_table_not_found'));
    } else {
        echo rex_view::warning(rex_i18n::msg('yform_manager_table_nopermission'));
    }
}
