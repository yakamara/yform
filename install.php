<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$msg = '';

$phpmailer_addon = rex_addon::get('phpmailer');
if ($phpmailer_addon->exists('phpmailer') && $phpmailer_addon->isAvailable() && $phpmailer_addon->getVersion() >= '5.0-alpha7') {

    // paket installieren

} else {

    $msg = rex_i18n::msg('yform_install_phpmailer_version_problem', '5.0-alpha7');

    /*

    // AUTOINSTALL THESE PLUGINS
    $autoinstall = array('email', 'setup', 'manager');

    // GET ALL ADDONS & PLUGINS
    $all_addons = rex_read_addons_folder();
    $all_plugins = array();
    foreach ($all_addons as $_addon) {
        $all_plugins[$_addon] = rex_read_plugins_folder($_addon);
    }

    // DO AUTOINSTALL
    $pluginManager = new rex_pluginManager($all_plugins, $addonname);
    foreach ($autoinstall as $pluginname) {
        // INSTALL PLUGIN
        if (($instErr = $pluginManager->install($pluginname)) !== true) {
            $msg = $instErr;
        }

        // ACTIVATE PLUGIN
        if ($msg == '' && ($actErr = $pluginManager->activate($pluginname)) !== true) {
            $msg = $actErr;
        }

        if ($msg != '') {
            break;
        }
    }

    */

}

/*
if ($msg != '') {
    $REX['ADDON']['installmsg'][$addonname] = $msg;

} else {
    // INSTALL ADDON
    $sql = rex_sql::factory();

    if ($sql->hasError()) {
        $msg = 'MySQL-Error: ' . $sql->getErrno() . '<br />';
        $msg .= $sql->getError();

        $REX['ADDON']['install'][$addonname] = 0;
        $REX['ADDON']['installmsg'][$addonname] = $msg;
    } else {
        $REX['ADDON']['install'][$addonname] = 1;
    }
}
*/