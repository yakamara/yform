<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform'));

$page = rex_request('page', 'string', '');

$yform = new rex_yform;
$yform->setHiddenField('page', $page);
$yform->setObjectparams('real_field_names',true);
$yform->setObjectparams('hide_top_warning_messages', true);
$yform->setObjectparams('form_showformafterupdate',1);
$yform->setValueField('upload', array(
    'name'     => 'importfile',
    'label'    => rex_i18n::msg('yform_manager_tableset_import_jsonimportfile'),
    'max_size' => '1000', // max size in kb or range 100,500
    'types'    => '.json', // allowed extensions ".gif,.png"
    'required' => 1,
    'messages' => array(
        rex_i18n::msg('yform_manager_table_importset_warning_min'),
        rex_i18n::msg('yform_manager_table_importset_warning_max'),
        rex_i18n::msg('yform_manager_table_importset_warning_type'),
        rex_i18n::msg('yform_manager_table_importset_warning_selectfile')
    ),
    'modus'    => 'no_save',
    'no_db'    => 'no_db'
));

$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {

    try {

        $content = file_get_contents(rex_path::addonData('yform','uploads/'.$yform->objparams['value_pool']['email']['importfile']));
        rex_yform_manager_table_api::importTablesets($content);
        echo rex_view::success(rex_i18n::msg('yform_manager_tableset_import_success'));

    } catch (Exception $e) {
        echo rex_view::warning(rex_i18n::msg('yform_manager_tableset_import_failed', '', $e->getMessage()));

    }
    rex_file::delete(rex_path::addonData('yform','uploads/'.$yform->objparams['value_pool']['email']['importfile']));

} else if ($yform->objparams['send']) {

    echo rex_view::warning(rex_i18n::msg('yform_manager_tableset_import_warning_selectfile'));

}

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', rex_i18n::msg('yform_manager_tableset_import'));
$fragment->setVar('body', $form, false);
// $fragment->setVar('buttons', $buttons, false);
$form = $fragment->parse('core/page/section.php');

echo $form;