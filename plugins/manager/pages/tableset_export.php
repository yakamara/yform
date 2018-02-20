<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform'));
$_csrf_key = 'tableset_export';

$page = rex_request('page', 'string', '');

$yform_tables = [];
foreach (rex_yform_manager_table::getAll() as $g_table) {
    $table_name = $g_table->getTableName();

    if ('[translate:'.$table_name.']' != rex_i18n::msg($table_name)) {
        $table_name = rex_i18n::msg($table_name);
    }

    $yform_tables[$g_table->getTableName()] = $table_name.' ['.$g_table->getTableName().']';
}

$yform = new rex_yform();
$yform->setHiddenField('page', $page);
$yform->setObjectparams('real_field_names', true);
$yform->setObjectparams('form_name', $_csrf_key);
$yform->setValueField('select', ['table_names', rex_i18n::msg('yform_manager_tables'), $yform_tables, 'multiple' => 1]);
$yform->setValidateField('empty', ['table_names', rex_i18n::msg('yform_manager_export_error_empty')]);
$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {
    try {
        $table_names = rex_request('table_names');
        $fileContent = rex_yform_manager_table_api::exportTablesets($table_names);

        $tablenames = implode('_', $table_names);
        if (strlen($tablenames) > 100) {
            $tables = substr($tablenames, 0, 100).'_etc_';
        }

        $fileName = 'yform_manager_tableset_export_tables_'.$tablenames.'_'.date('YmdHis').'.json';
        header('Content-Disposition: attachment; filename="' . $fileName . '"; charset=utf-8');
        rex_response::sendContent($fileContent, 'application/octetstream');
        exit;

    } catch (Exception $e) {
        echo rex_view::warning($this->msg('table_export_failed', '', $e->getMessage()));
    }
}

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yform_manager_tableset_export'));
$fragment->setVar('body', $form, false);
// $fragment->setVar('buttons', $buttons, false);
$form = $fragment->parse('core/page/section.php');

echo $form;
