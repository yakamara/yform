<?php

use Redaxo\YForm\Manager\Importer;

$_csrf_key ??= '';
$show_importform = true;

$delimiter = rex_request('delimiter', 'string', ';');
$missing_columns = rex_request('missing_columns', 'int', 1);

if (1 == rex_request('send', 'int', 0)) {
    if (!isset($_FILES['file_new']) || '' == $_FILES['file_new']['tmp_name']) {
        echo rex_view::error(rex_i18n::msg('yform_manager_import_error_missingfile'));
    } else {
        $Importer = new Importer($this->table);
        $Importer->setDelimiter($delimiter);
        $Importer->setImportFilePath($_FILES['file_new']['tmp_name']);
        $Importer->setMissingColumnsMode($missing_columns);
        $Importer->import();
        $messages = $Importer->getMessages();

        if (isset($messages['info'])) {
            foreach ($messages['info'] as $message) {
                echo rex_view::info($message);
            }
            $show_importform = false;
        }

        if ($Importer->hasErrors()) {
            foreach ($messages['error'] as $message) {
                echo rex_view::error($message);
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
        <p>' . rex_i18n::msg('yform_manager_import_csv_info') . '</p>
        <fieldset>
            ' . $hidden . '
    ';

    $formElements = [];

    foreach (Importer::MISSING_COLUMNS_OPTIONS as $mode => $label) {
        $n = [];
        $n['label'] = '<label>' . rex_i18n::msg($label) . '</label>';
        $n['field'] = '<input type="radio" name="missing_columns" value="' . $mode . '"' . (('' . $mode == $missing_columns) ? 'checked' : '') . ' />';
        $formElements[] = $n;
    }

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $radios = $fragment->parse('core/form/radio.php');

    $formElements = [];
    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_if_no_column') . '</label>';
    $n['field'] = $radios;
    $formElements[] = $n;

    $a = new rex_select();
    $a->setName('delimiter');
    $a->setId('delimiter');
    foreach (Importer::DELIMITER_OPTIONS as $key => $value) {
        $a->addOption(rex_escape($key) . ' (' . rex_escape($value) . ')', $key);
    }
    $a->setSelected($delimiter);

    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_delimiter') . '</label>';
    $n['field'] = '<div class="yform-select-style">' . $a->get() . '</div>';
    $formElements[] = $n;

    $n = [];
    $n['label'] = '<label>' . rex_i18n::msg('yform_manager_import_file') . '</label>';
    $n['field'] = '<input class="form-control" type="file" name="file_new" />'
                . rex_csrf_token::factory($_csrf_key)->getHiddenField();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/form.php');

    $content .= '</fieldset>';

    $formElements = [];

    $n = [];
    $n['field'] = '<a class="btn btn-abort" href="' . rex_url::currentBackendPage($this->getLinkVars()) . '">' . rex_i18n::msg('form_abort') . '</a>';
    $formElements[] = $n;

    $n = [];
    $n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . rex_i18n::msg('yform_manager_import_start') . '">' . rex_i18n::msg('yform_manager_import_start') . '</button>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $buttons = $fragment->parse('core/form/submit.php');

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', rex_i18n::msg('yform_manager_import_csv'), false);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    $content = $fragment->parse('core/page/section.php');

    $content = '
    <form action="' . rex_url::currentBackendPage() . '" data-pjax="false" method="post" enctype="multipart/form-data">
        ' . $content . '
    </form>';

    echo $content;
}
