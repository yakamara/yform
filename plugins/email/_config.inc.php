<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$mypage = 'email';

$REX['ADDON']['yform']['classpaths']['action'][] = $REX['INCLUDE_PATH'] . '/addons/yform/plugins/email/classes/action/';

include $REX['INCLUDE_PATH'] . '/addons/yform/plugins/email/classes/basic/class.rex_yform_emailtemplate.inc.php';

if ($REX['REDAXO'] && !$REX['SETUP']) {
    rex_i18n::msgappendFile($REX['INCLUDE_PATH'] . '/addons/yform/plugins/email/lang/');

    $REX['ADDON']['version'][$mypage] = '4.12';
    $REX['ADDON']['author'][$mypage] = 'Jan Kristinus';
    $REX['ADDON']['supportpage'][$mypage] = 'www.yakamara.de/tag/redaxo/';
    $REX['PERM'][] = 'yform[email]';

    if ($REX['USER'] && (rex::getUser()->isAdmin() || $REX['USER']->hasPerm('yform[email]'))) {
        $REX['ADDON']['yform']['SUBPAGES'][] = array('email' , rex_i18n::msg('yform_email_templates'));
    }

}
