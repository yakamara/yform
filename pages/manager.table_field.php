<?php

use Yakamara\YForm\Manager\Manager;
use Yakamara\YForm\Manager\Table\Table;

echo rex_view::title(rex_i18n::msg('yform'));

$table_name = rex_request('table_name', 'string');
$table = Table::get($table_name);

if ($table) {
    try {
        $page = new Manager();
        $page->setTable($table);
        $page->setLinkVars(['page' => 'yform/manager/table_field']);
        echo $page->getFieldPage();
    } catch (Exception $e) {
        $message = nl2br($e->getMessage() . "\n" . $e->getTraceAsString());
        echo rex_view::warning($message);
    }
} else {
    echo rex_view::warning(rex_i18n::msg('yform_table_not_found'));
}
