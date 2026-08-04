<?php

use Redaxo\Core\Database\Sql;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\Message;
use Redaxo\Core\View\View;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\YForm;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo View::title(I18n::msg('yform'));
$_csrf_key = 'table_migrate';

$page = Request::request('page', 'string', '');

$available_tables = Sql::factory()->getTablesAndViews();

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
$yform->setValueField('choice', ['name' => 'table_name', 'label' => I18n::msg('yform_table'), 'choices' => $missing_tables]);
$yform->setValueField('checkbox', ['schema_overwrite', I18n::msg('yform_manager_table_schema_overwrite')]);
$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {
    $table_name = (string) $yform->objparams['value_pool']['sql']['table_name'];
    $schema_overwrite = (int) $yform->objparams['value_pool']['sql']['schema_overwrite'];

    try {
        Api::migrateTable($table_name, (0 == $schema_overwrite) ? false : true); // with convert id / auto_increment finder
        echo Message::success(I18n::msg('yform_manager_table_migrated_success'));

        unset($missing_tables[$table_name]);

        $yform = new YForm();
        $yform->setObjectparams('form_showformafterupdate', 1);
        $yform->setHiddenField('page', $page);
        $yform->setValueField('choice', ['name' => 'table_name', 'label' => I18n::msg('yform_table'), 'choices' => $missing_tables]);
        $yform->setValueField('checkbox', ['schema_overwrite', I18n::msg('yform_manager_table_schema_overwrite')]);
        $form = $yform->getForm();
    } catch (Exception $e) {
        echo Message::warning(I18n::msg('yform_manager_table_migrated_failed', $table_name, $e->getMessage()));
    }
}

echo Message::info(I18n::msg('yform_manager_table_migrate_info'));

$fragment = new Fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', I18n::msg('yform_manager_table_migrate'));
$fragment->setVar('body', $form, false);
// $fragment->setVar('buttons', $buttons, false);
$form = $fragment->parse('core/page/section.php');

echo $form;
