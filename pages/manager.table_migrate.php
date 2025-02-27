<?php

use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\YForm;

echo rex_view::title(rex_i18n::msg('yform'));
$_csrf_key = 'table_migrate';

$page = rex_request('page', 'string', '');

$available_tables = rex_sql::factory()->getTablesAndViews();

$yform_tables = [];
$missing_tables = [];

foreach (Table::getAll() as $g_table) {
    $yform_tables[] = $g_table->getTableName();
}

foreach ($available_tables as $a_table) {
    if (!in_array($a_table, $yform_tables)) {
        $missing_tables[$a_table] = $a_table;
    }
}

$yform = new YForm();
$yform->setObjectparams('form_showformafterupdate', 1);
$yform->setObjectparams('form_name', $_csrf_key);
$yform->setHiddenField('page', $page);
$yform->setValueField('choice', ['name' => 'table_name', 'label' => rex_i18n::msg('yform_table'), 'choices' => $missing_tables]);
$yform->setValueField('checkbox', ['schema_overwrite', rex_i18n::msg('yform_manager_table_schema_overwrite')]);
$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {
    $table_name = (string) $yform->objparams['value_pool']['sql']['table_name'];
    $schema_overwrite = (int) $yform->objparams['value_pool']['sql']['schema_overwrite'];

    try {
        Api::migrateTable($table_name, (0 == $schema_overwrite) ? false : true); // with convert id / auto_increment finder
        echo rex_view::success(rex_i18n::msg('yform_manager_table_migrated_success'));

        unset($missing_tables[$table_name]);

        $yform = new YForm();
        $yform->setObjectparams('form_showformafterupdate', 1);
        $yform->setHiddenField('page', $page);
        $yform->setValueField('choice', ['name' => 'table_name', 'label' => rex_i18n::msg('yform_table'), 'choices' => $missing_tables]);
        $yform->setValueField('checkbox', ['schema_overwrite', rex_i18n::msg('yform_manager_table_schema_overwrite')]);
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
