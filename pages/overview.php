<?php

echo rex_view::title('yform');

if (rex::getUser()->isAdmin()) {

    $content = '';
    $searchtext = 'module:yform_basic_output';

    $gm = rex_sql::factory();
    $gm->setQuery('select * from rex_module where output LIKE "%' . $searchtext . '%"');

    $module_id = 0;
    $module_name = '';
    foreach ($gm->getArray() as $module) {
        $module_id = $module['id'];
        $module_name = $module['name'];
    }

    $yform_module_name = 'YForm Formbuilder';

    if (rex_request('install',"integer") == 1) {

        $input = rex_file::get(rex_path::addon('yform','module/module_input.inc'));
        $output = rex_file::get(rex_path::addon('yform','module/module_output.inc'));

        $mi = rex_sql::factory();
        // $mi->debugsql = 1;
        $mi->setTable('rex_module');
        $mi->setValue('input', $input);
        $mi->setValue('output', $output);

        if ($module_id == rex_request('module_id','integer',-1)) {
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

    $content .= '<p>'.$this->i18n('install_modul_description').'<br /><br />';

    if ($module_id > 0) {
        $content .= '<p class="btn btn-primary"><a href="index.php?page=yform/overview&amp;install=1&amp;module_id=' . $module_id . '" class="rex-button">' . $this->i18n('install_update_module', htmlspecialchars($module_name)) . '</a></p>';

    }else {
        $content .= '<p class="btn btn-primary"><a href="index.php?page=yform/overview&amp;install=1" class="rex-button">' . $this->i18n('install_yform_modul', $yform_module_name) . '</a></p>';

    }
    $content .= '</p>';

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'info');
    $fragment->setVar('title', $this->i18n('install_modul'), false);
    $fragment->setVar('body', $content , false);
    echo $fragment->parse('core/page/section.php');

}



$content = rex_i18n::rawMsg('yform_description_all', false) . rex_yform::showHelp(true, true);

$fragment = new rex_fragment();
$fragment->setVar('class', 'info');
$fragment->setVar('title', $this->i18n('description'), false);
$fragment->setVar('body', $content , false);
echo $fragment->parse('core/page/section.php');
