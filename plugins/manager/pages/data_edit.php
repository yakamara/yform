<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */


echo rex_view::title(rex_i18n::msg('yform'));

$table_name = rex_request('table_name', 'string');
$table = rex_yform_manager_table::get($table_name);

if ($table && $REX['USER'] && (rex::getUser()->isAdmin() || $REX['USER']->hasPerm('yform[table:' . $table_name . ']')) ) {

    try {

        $page = new rex_yform_manager();
        $page->setTable($table);
        $page->setLinkVars( array('page' => 'yform', 'subpage' => 'manager', 'tripage' => 'data_edit', 'table_name' => $table->getTableName()) );
        echo $page->getDataPage();

    } catch (Exception $e) {
      echo rex_view::warning(rex_i18n::msg('yform_table_not_found'));

    }

}
