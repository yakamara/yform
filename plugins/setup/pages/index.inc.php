<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

rex_title('yform', $REX['ADDON']['yform']['SUBPAGES']);

$searchtext = 'module:yform_basic_out';

$gm = rex_sql::factory();
$gm->setQuery('select * from rex_module where output LIKE "%' . $searchtext . '%"');

$module_id = 0;
$module_name = '';
foreach ($gm->getArray() as $module) {
    $module_id = $module['id'];
    $module_name = $module['name'];
}

if (isset($_REQUEST['install']) && $_REQUEST['install'] == 1) {

    $yform_module_name = 'yform Formbuilder';

    $in = rex_get_file_contents($REX['INCLUDE_PATH'] . '/addons/yform/plugins/setup/module/module_in.inc');
    $out = rex_get_file_contents($REX['INCLUDE_PATH'] . '/addons/yform/plugins/setup/module/module_out.inc');

    $mi = rex_sql::factory();
    // $mi->debugsql = 1;
    $mi->setTable('rex_module');
    $mi->setValue('eingabe', addslashes($in));
    $mi->setValue('ausgabe', addslashes($out));

    if (isset($_REQUEST['module_id']) && $module_id == $_REQUEST['module_id']) {
        $mi->setWhere('id="' . $module_id . '"');
        $mi->update();
        echo rex_view::info('Modul "' . $module_name . '" wurde aktualisiert');

    } else {
        $mi->setValue('name', $yform_module_name);
        $mi->insert();
        $module_id = (int) $mi->getLastId();
        $module_name = $yform_module_name;
        echo rex_view::info('yform Modul wurde angelegt unter "' . $yform_module_name . '"');

    }

}

echo '

<div class="rex-addon-output">
    <h2 class="rex-hl2">' . rex_i18n::msg('yform_setup_install_modul') . '</h2>
    <div class="rex-addon-content">
    <p>' . rex_i18n::msg('yform_setup_install_modul_description') . '</p>

    <p class="rex-button"><a href="index.php?page=yform&amp;subpage=setup&amp;install=1" class="rex-button">' . rex_i18n::msg('yform_setup_install_yform_modul') . '</a></p>';

        if ($module_id > 0) {
            echo '<p class="rex-button"><a href="index.php?page=yform&amp;subpage=setup&amp;install=1&amp;module_id=' . $module_id . '" class="rex-button">' . rex_i18n::msg('yform_setup_update_following_modul', htmlspecialchars($module_name)) . '</a></p>';
        }

echo '
    </div>
</div>';
