<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$mypage = 'yform';

$REX['ADDON']['name'][$mypage] = 'yform';
$REX['ADDON']['perm'][$mypage] = 'yform[]';
$REX['ADDON']['version'][$mypage] = '4.13';
$REX['ADDON']['author'][$mypage] = 'Jan Kristinus';
$REX['ADDON']['supportpage'][$mypage] = 'www.yakamara.de/tag/yform/';
$REX['PERM'][] = 'yform[]';

if (empty($REX['ADDON']['yform']['classpaths']['value']) or !is_array($REX['ADDON']['yform']['classpaths']['value'])) {
    $REX['ADDON']['yform']['classpaths']['value'] = array();
}
if (empty($REX['ADDON']['yform']['classpaths']['action']) or !is_array($REX['ADDON']['yform']['classpaths']['action'])) {
    $REX['ADDON']['yform']['classpaths']['action'] = array();
}
if (empty($REX['ADDON']['yform']['classpaths']['validate']) or !is_array($REX['ADDON']['yform']['classpaths']['validate'])) {
    $REX['ADDON']['yform']['classpaths']['validate'] = array();
}

$REX['ADDON']['yform']['classpaths']['value'][] = $REX['INCLUDE_PATH'] . '/addons/yform/classes/value/';
$REX['ADDON']['yform']['classpaths']['validate'][] = $REX['INCLUDE_PATH'] . '/addons/yform/classes/validate/';
$REX['ADDON']['yform']['classpaths']['action'][] = $REX['INCLUDE_PATH'] . '/addons/yform/classes/action/';

$REX['ADDON']['yform']['templatepaths'][] = $REX['INCLUDE_PATH'] . '/addons/yform/templates/';
rex_register_extension('ADDONS_INCLUDED', function () {
    global $REX;
    $REX['ADDON']['yform']['templatepaths'][] = rex_path::addonData('yform', 'templates/');
}, array(), REX_EXTENSION_EARLY);

include_once $REX['INCLUDE_PATH'] . '/addons/' . $mypage . '/classes/basic/class.rex_radio.inc.php';
include_once $REX['INCLUDE_PATH'] . '/addons/' . $mypage . '/classes/basic/class.rex_yform_list.inc.php';
include_once $REX['INCLUDE_PATH'] . '/addons/' . $mypage . '/classes/basic/class.rex_yform.inc.php';

if ($REX['REDAXO'] && $REX['USER']) {
    rex_i18n::msgappendFile($REX['INCLUDE_PATH'] . '/addons/' . $mypage . '/lang/');

    $REX['ADDON'][$mypage]['SUBPAGES'] = array();
    $REX['ADDON'][$mypage]['SUBPAGES'][] = array( '' , rex_i18n::msg('yform_overview'));
    if (rex::getUser()->isAdmin()) {
        $REX['ADDON'][$mypage]['SUBPAGES'][] = array('description' , rex_i18n::msg('yform_description'));
    }

    function rex_yform_css($params)
    {
        global $REX;
        $params['subject'] .= "\n  " . '<link rel="stylesheet" type="text/css" href="../files/addons/yform/yform.css" media="screen, projection, print" />';
        $params['subject'] .= "\n  " . '<script src="../files/addons/yform/manager.js" type="text/javascript"></script>';
        if ($REX['REDAXO']) {
            $params['subject'] .= "\n  " . '<link rel="stylesheet" type="text/css" href="../files/addons/yform/manager.css" media="screen, projection, print" />';
        }
        return $params['subject'];
    }

    rex_register_extension('PAGE_HEADER', 'rex_yform_css');

}