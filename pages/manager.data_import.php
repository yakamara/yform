<?php

use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Form\Select\Select;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\Message;
use Yakamara\YForm\Manager\Importer;
use Yakamara\YForm\Manager\Manager;

use function Redaxo\Core\View\escape;

/** @var Manager $this */


$_csrf_key ??= '';
$show_importform = true;

$delimiter = Request::request('delimiter', 'string', ';');
$missing_columns = Request::request('missing_columns', 'int', 1);

if (1 == Request::request('send', 'int', 0)) {
    if (!isset($_FILES['file_new']) || '' == $_FILES['file_new']['tmp_name']) {
        echo Message::error(I18n::msg('yform_manager_import_error_missingfile'));
    } else {
        $Importer = new Importer($this->table);
        $Importer->setDelimiter($delimiter);
        $Importer->setImportFilePath($_FILES['file_new']['tmp_name']);
        $Importer->setMissingColumnsMode($missing_columns);
        $Importer->import();
        $messages = $Importer->getMessages();

        if (isset($messages['info'])) {
            foreach ($messages['info'] as $message) {
                echo Message::info($message);
            }
            $show_importform = false;
        }

        if ($Importer->hasErrors()) {
            foreach ($messages['error'] as $message) {
                echo Message::error($message);
            }
            $show_importform = true;
        }
    }
}

if ($show_importform) {
    $hidden = '
        <input type="hidden" name="func" value="import" />
        <input type="hidden" name="send" value="1" />';

    foreach ($this->getLinkVars() as $k => $v) {
        $hidden .= '<input type="hidden" name="' . $k . '" value="' . addslashes($v) . '" />';
    }

    $content = '
        <p>' . I18n::msg('yform_manager_import_csv_info') . '</p>
        <fieldset>
            ' . $hidden . '
    ';

    $formElements = [];

    foreach (Importer::MISSING_COLUMNS_OPTIONS as $mode => $label) {
        $n = [];
        $n['label'] = '<label>' . I18n::msg($label) . '</label>';
        $n['field'] = '<input type="radio" name="missing_columns" value="' . $mode . '"' . (('' . $mode == $missing_columns) ? 'checked' : '') . ' />';
        $formElements[] = $n;
    }

    $fragment = new Fragment();
    $fragment->setVar('elements', $formElements, false);
    $radios = $fragment->parse('core/form/radio.php');

    $formElements = [];
    $n = [];
    $n['label'] = '<label>' . I18n::msg('yform_manager_import_if_no_column') . '</label>';
    $n['field'] = $radios;
    $formElements[] = $n;

    $a = new Select();
    $a->setName('delimiter');
    $a->setId('delimiter');
    foreach (Importer::DELIMITER_OPTIONS as $key => $value) {
        $a->addOption(escape($key) . ' (' . escape($value) . ')', $key);
    }
    $a->setSelected($delimiter);

    $n = [];
    $n['label'] = '<label>' . I18n::msg('yform_manager_import_delimiter') . '</label>';
    $n['field'] = '<div class="yform-select-style">' . $a->get() . '</div>';
    $formElements[] = $n;

    $n = [];
    $n['label'] = '<label>' . I18n::msg('yform_manager_import_file') . '</label>';
    $n['field'] = '<input class="form-control" type="file" name="file_new" />'
                . CsrfToken::factory($_csrf_key)->getHiddenField();
    $formElements[] = $n;

    $fragment = new Fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    $content .= '</fieldset>';

    $formElements = [];

    $n = [];
    $n['field'] = '<a class="btn btn-abort" href="' . Url::currentBackendPage($this->getLinkVars()) . '">' . I18n::msg('form_abort') . '</a>';
    $formElements[] = $n;

    $n = [];
    $n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . I18n::msg('yform_manager_import_start') . '">' . I18n::msg('yform_manager_import_start') . '</button>';
    $formElements[] = $n;

    $fragment = new Fragment();
    $fragment->setVar('elements', $formElements, false);
    $buttons = $fragment->parse('core/form/submit.php');

    $fragment = new Fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', I18n::msg('yform_manager_import_csv'), false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    $content = $fragment->parse('core/page/section.php');

    $content = '
    <form action="' . Url::currentBackendPage() . '" data-pjax="false" method="post" enctype="multipart/form-data">
        ' . $content . '
    </form>';

    echo $content;
}
