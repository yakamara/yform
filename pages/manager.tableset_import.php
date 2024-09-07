<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform'));
$_csrf_key = 'tableset_import';

$page = rex_request('page', 'string', '');

$yform = new \Yakamara\YForm\YForm();
$yform->setHiddenField('page', $page);
$yform->setObjectparams('real_field_names', true);
$yform->setObjectparams('form_name', $_csrf_key);
$yform->setValueField('upload', [
    'name' => 'importfile',
    'label' => rex_i18n::msg('yform_manager_tableset_import_jsonimportfile'),
    'sizes' => '1000',
    'types' => '.json',
    'required' => true,
    'messages' => [
        rex_i18n::msg('yform_manager_table_importset_warning_min'),
        rex_i18n::msg('yform_manager_table_importset_warning_max'),
        rex_i18n::msg('yform_manager_table_importset_warning_type'),
        rex_i18n::msg('yform_manager_tableset_import_warning_selectfile'),
    ],
]);

$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {
    try {
        $filder = $yform->objparams['value_pool']['files']['importfile'][2];
        $content = file_get_contents($filder);
        \Yakamara\YForm\Manager\Table\Api::importTablesets($content);
        echo rex_view::success(rex_i18n::msg('yform_manager_tableset_import_success'));
    } catch (Exception $e) {
        echo rex_view::warning(rex_i18n::msg('yform_manager_tableset_import_failed', $e->getMessage()));
    }
}

if ('' != $form) {
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', rex_i18n::msg('yform_manager_tableset_import'));
    $fragment->setVar('body', $form, false);
    // $fragment->setVar('buttons', $buttons, false);
    $form = $fragment->parse('core/page/section.php');

    echo $form;
}
