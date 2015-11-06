<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform'));

$page = rex_request('page', 'string', '');

$available_tables = rex_sql::showTables();
$yform_tables = array();
$missing_tables = array();

foreach (rex_yform_manager_table::getAll() as $g_table) {
    $yform_tables[] = $g_table->getTableName();
}

foreach ($available_tables as $a_table) {
    if ( !in_array($a_table, $yform_tables)) {
        $missing_tables[$a_table] = $a_table;
    }

}

$yform = new rex_yform;
$yform->setObjectparams('form_showformafterupdate',1);
$yform->setHiddenField('page', $page);
$yform->setValueField('select', array('table_name', rex_i18n::msg('yform_table'), $missing_tables));
$yform->setValueField('checkbox', array('convert_id', rex_i18n::msg('yform_manager_migrate_table_id_convert')));
$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {

    $table_name = $yform->objparams['value_pool']['sql']['table_name'];
    $convert_id = $yform->objparams['value_pool']['sql']['convert_id'];

    try {

        rex_yform_manager_table_api::migrateTable($table_name, $convert_id); // with convert id / auto_increment finder
        echo rex_view::success(rex_i18n::msg('yform_manager_table_migrated_success'));

        unset($missing_tables[$table_name]);

        $yform = new rex_yform;
        $yform->setObjectparams('form_showformafterupdate',1);
        $yform->setHiddenField('page', $page);
        $yform->setValueField('select', array('table_name', rex_i18n::msg('yform_table'), $missing_tables));
        $yform->setValueField('checkbox', array('convert_id', rex_i18n::msg('yform_manager_migrate_table_id_convert')));
        $form = $yform->getForm();

    } catch (Exception $e) {
        echo rex_view::warning(rex_i18n::msg('yform_manager_table_migrated_failed', $table_name, $e->getMessage()));

    }

}

echo rex_view::info(rex_i18n::msg('yform_manager_table_migrate_info'));

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yform_manager_table_migrate'));
$fragment->setVar('body', $form, false);
// $fragment->setVar('buttons', $buttons, false);
$form = $fragment->parse('core/page/section.php');

echo $form;