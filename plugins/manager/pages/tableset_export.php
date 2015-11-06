<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform'));

$page = rex_request('page', 'string', '');

$yform_tables = array();
foreach (rex_yform_manager_table::getAll() as $g_table) {
    $yform_tables[$g_table->getTableName()] = rex_i18n::translate("translate:".$g_table->getTableName()).' ['.$g_table->getTableName().']';
}

$yform = new rex_yform;
$yform->setHiddenField('page', $page);
$yform->setObjectparams('real_field_names', true);
$yform->setObjectparams('hide_top_warning_messages', true);
$yform->setValueField('select', array('table_names', rex_i18n::msg('yform_manager_tables'), $yform_tables, 'multiple'=>1));
$yform->setValidateField('empty', array('table_names', ''));
$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {

    try {

        $table_names = rex_request("table_names");
        $return = rex_yform_manager_table_api::exportTablesets($table_names);

        $file_name = 'yform_manager_tableset_export_tables_'.date("YmdHis").'.json';

        ob_end_clean();

        header('Content-Type: application/json');
        header('Charset: UTF-8');
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
        header('Content-Length: ' . strlen($return));
        header('Pragma: public');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        echo $return;

        exit;

    } catch (Exception $e) {

        echo rex_view::warning($this->msg('table_export_failed', '', $e->getMessage()));

    }

} else if ($yform->objparams['send']) {

    echo rex_view::warning(rex_i18n::msg('yform_manager_export_error_empty'));

}

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yform_manager_tableset_export'));
$fragment->setVar('body', $form, false);
// $fragment->setVar('buttons', $buttons, false);
$form = $fragment->parse('core/page/section.php');

echo $form;