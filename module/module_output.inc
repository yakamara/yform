<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// module:yform_basic_output
// v1.0.1
//--------------------------------------------------------------------------------

$yform = new rex_yform;
if ("REX_VALUE[7]" == 1) { $yform->setDebug(TRUE); }
$form_data = "REX_VALUE[id=3 output=html]";
$form_data = trim(rex_yform::unhtmlentities($form_data));
$yform->setObjectparams('form_action', rex_getUrl(REX_ARTICLE_ID,REX_CLANG_ID));
$yform->setFormData($form_data);

// action - showtext
if("REX_VALUE[id=11]" != "") {
    $html = "0"; // plaintext
    if('REX_VALUE[11]' == 1) $html = "1"; // html
    if('REX_VALUE[11]' == 2) $html = "2"; // textile

    $e3 = ''; $e4 = '';
    if ($html == "0") {
        $e3 = '<div class="alert alert-success">';
        $e4 = '</div>';
    }

    $t = str_replace("<br />","",rex_yform::unhtmlentities('REX_VALUE[6]'));
    $yform->setActionField("showtext",array(
    $t,
    $e3,
    $e4,
    $html // als HTML interpretieren
    )
    );
}

$form_type = "REX_VALUE[1]";

// action - email
if ($form_type == "1" || $form_type == "2") {
    $mail_from = ('REX_VALUE[2]' != '') ? 'REX_VALUE[2]' : rex::getErrorEmail();
    $mail_to = ('REX_VALUE[12]' != '') ? 'REX_VALUE[12]' : rex::getErrorEmail();
    $mail_subject = "REX_VALUE[4]";
    $mail_body = str_replace("<br />","",rex_yform::unhtmlentities('REX_VALUE[5]'));
    $yform->setActionField("email", array(
    $mail_from,
    $mail_to,
    $mail_subject,
    $mail_body
    )
    );
}

// action - db
if ($form_type == "0" || $form_type == "2") {
    $yform->setObjectparams('main_table', 'REX_VALUE[8]');

    //getdata
    if ("REX_VALUE[10]" != "")
        $yform->setObjectparams("getdata",TRUE);

    $yform->setActionField("db", array(
    "REX_VALUE[8]", // table
    $yform->objparams["main_where"], // where
    )
    );
}

echo $yform->getForm();

?>