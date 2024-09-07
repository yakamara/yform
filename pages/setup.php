<?php

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

echo rex_view::title($this->i18n('yform'));

if (rex::getUser()->isAdmin() && rex_addon::get('structure')->isAvailable()) {
    $content = '';
    $searchtext = 'module:yform_basic_output';

    $gm = rex_sql::factory();
    $gm->setQuery('select * from ' . rex::getTable('module') . ' where output LIKE "%' . $searchtext . '%"');

    $module_id = 0;
    $module_name = '';
    foreach ($gm->getArray() as $module) {
        $module_id = $module['id'];
        $module_name = $module['name'];
    }

    $yform_module_name = 'YForm Formbuilder';

    if (1 == rex_request('install', 'integer')) {
        $input = rex_file::get(rex_path::addon('yform', 'module/module_input.inc'));
        $output = rex_file::get(rex_path::addon('yform', 'module/module_output.inc'));

        $mi = rex_sql::factory();
        // $mi->debugsql = 1;
        $mi->setTable(rex::getTable('module'));
        $mi->setValue('input', $input);
        $mi->setValue('output', $output);

        if ($module_id == rex_request('module_id', 'integer', -1)) {
            $mi->setWhere('id="' . $module_id . '"');
            $mi->update();
            echo rex_view::success('Modul "' . $module_name . '" wurde aktualisiert');
        } else {
            $mi->setValue('name', $yform_module_name);
            $mi->insert();
            $module_id = (int) $mi->getLastId();
            $module_name = $yform_module_name;
            echo rex_view::success('yform Modul wurde angelegt unter "' . $yform_module_name . '"');
        }
    }

    $content .= '<p>' . $this->i18n('install_modul_description') . '</p>';

    if ($module_id > 0) {
        $content .= '<p><a class="btn btn-primary" href="index.php?page=yform/setup&amp;install=1&amp;module_id=' . $module_id . '" class="rex-button">' . $this->i18n('install_update_module', rex_escape((string) $module_name)) . '</a></p>';
    } else {
        $content .= '<p><a class="btn btn-primary" href="index.php?page=yform/setup&amp;install=1" class="rex-button">' . $this->i18n('install_yform_modul', $yform_module_name) . '</a></p>';
    }

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('install_modul'), false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
}

$content = rex_i18n::rawMsg('yform_description_all');

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('description'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('description_type_heading'), false);
$fragment->setVar('body', \Yakamara\YForm\YForm::showHelp(), false);
echo $fragment->parse('core/page/section.php');
