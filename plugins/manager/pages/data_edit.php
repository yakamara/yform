<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// echo rex_view::title(rex_i18n::msg('yform'));

$table_name = rex_request('table_name', 'string');
$table = rex_yform_manager_table::get($table_name);

if ($table && $table->isGranted('VIEW', rex::getUser())) {
    try {
        $page = new rex_yform_manager();
        $page->setTable($table);
        $page->setLinkVars(['page' => rex_be_controller::getCurrentPage(), 'table_name' => $table->getTableName()]);
        echo $page->getDataPage();
    } catch (Exception $e) {
        echo rex_view::warning(nl2br($e->getMessage()."\n".$e->getTraceAsString()));
    }
} else {
    if (!$table) {
        echo rex_view::warning(rex_i18n::msg('yform_table_not_found'));
    } else {
        echo rex_view::warning(rex_i18n::msg('yform_manager_table_nopermission'));
    }
}
