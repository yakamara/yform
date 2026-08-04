<?php

use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\Message;
use Redaxo\Core\View\View;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\YForm;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo View::title(I18n::msg('yform'));
$_csrf_key = 'tableset_import';

$page = Request::request('page', 'string', '');

$yform = new YForm();
$yform->setHiddenField('page', $page);
$yform->setObjectparams('real_field_names', true);
$yform->setObjectparams('form_name', $_csrf_key);
$yform->setValueField('upload', [
    'name' => 'importfile',
    'label' => I18n::msg('yform_manager_tableset_import_jsonimportfile'),
    'sizes' => '1000',
    'types' => '.json',
    'required' => true,
    'messages' => [
        I18n::msg('yform_manager_table_importset_warning_min'),
        I18n::msg('yform_manager_table_importset_warning_max'),
        I18n::msg('yform_manager_table_importset_warning_type'),
        I18n::msg('yform_manager_tableset_import_warning_selectfile'),
    ],
]);

$form = $yform->getForm();

if ($yform->objparams['actions_executed']) {
    try {
        $filder = $yform->objparams['value_pool']['files']['importfile'][2];
        $content = file_get_contents($filder);
        Api::importTablesets($content);
        echo Message::success(I18n::msg('yform_manager_tableset_import_success'));
    } catch (Exception $e) {
        echo Message::warning(I18n::msg('yform_manager_tableset_import_failed', $e->getMessage()));
    }
}

if ('' != $form) {
    $fragment = new Fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', I18n::msg('yform_manager_tableset_import'));
    $fragment->setVar('body', $form, false);
    // $fragment->setVar('buttons', $buttons, false);
    $form = $fragment->parse('core/page/section.php');

    echo $form;
}
