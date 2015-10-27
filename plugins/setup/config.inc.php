<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$mypage = 'setup';

if ($REX['REDAXO'] && !$REX['SETUP']) {

    rex_i18n::msgappendFile($REX['INCLUDE_PATH'] . '/addons/yform/plugins/setup/lang/');

    $REX['ADDON']['version'][$mypage] = '4.12';
    $REX['ADDON']['author'][$mypage] = 'Jan Kristinus';
    $REX['ADDON']['supportpage'][$mypage] = 'www.yakamara.de/tag/yform/';

    if ($REX['USER'] && rex::getUser()->isAdmin()) {
        $REX['ADDON']['yform']['SUBPAGES'][] = array('setup' , rex_i18n::msg('yform_setup'));
    }

}
