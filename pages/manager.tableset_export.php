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
foreach (\Yakamara\YForm\Manager\Table\Table::getAll() as $g_table) {
    $table_name = $g_table->getTableName();
    $yform_tables[$table_name] = $g_table->getNameLocalized() . ' [' . $table_name . ']';
}

$yform = new \Yakamara\YForm\YForm();
$yform->setHiddenField('page', $page);
$yform->setObjectparams('real_field_names', true);
$yform->setObjectparams('form_name', $_csrf_key);
$yform->setValueField('choice', ['name' => 'table_names', 'label' => rex_i18n::msg('yform_manager_tables'), 'choices' => $yform_tables, 'multiple' => true]);
$yform->setValidateField('empty', ['name' => 'table_names', 'label' => rex_i18n::msg('yform_manager_export_error_empty')]);
$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {
    try {
        $table_names = rex_request('table_names');
        $fileContent = \Yakamara\YForm\Manager\Table\Api::exportTablesets($table_names);

        $tablenames = implode('_', $table_names);
        if (mb_strlen($tablenames) > 100) {
            $tables = mb_substr($tablenames, 0, 100) . '_etc_';
        }

        $fileName = 'yform_manager_tableset_export_tables_' . $tablenames . '_' . date('YmdHis') . '.json';
        header('Content-Disposition: attachment; filename="' . $fileName . '"; charset=utf-8');
        rex_response::sendContent($fileContent, 'application/octetstream');
        exit;
    } catch (Exception $e) {
        echo rex_view::warning(rex_i18n::msg('table_export_failed', '', $e->getMessage()));
    }
}

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yform_manager_tableset_export'));
$fragment->setVar('body', $form, false);
// $fragment->setVar('buttons', $buttons, false);
$form = $fragment->parse('core/page/section.php');

echo $form;
