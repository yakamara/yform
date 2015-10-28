<?php

$REX['ADDON']['yform']['templatepaths'][] = $REX['INCLUDE_PATH'] . '/addons/yform/templates/';
rex_register_extension('ADDONS_INCLUDED', function () {

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

        $params['subject'] .= "\n  " . '<link rel="stylesheet" type="text/css" href="../files/addons/yform/yform.css" media="screen, projection, print" />';
        $params['subject'] .= "\n  " . '<script src="../files/addons/yform/manager.js" type="text/javascript"></script>';
        if ($REX['REDAXO']) {
            $params['subject'] .= "\n  " . '<link rel="stylesheet" type="text/css" href="../files/addons/yform/manager.css" media="screen, projection, print" />';
        }
        return $params['subject'];
    }

    rex_register_extension('PAGE_HEADER', 'rex_yform_css');

}