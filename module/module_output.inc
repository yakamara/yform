<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// module:yform_basic_output
// v1.1
//--------------------------------------------------------------------------------

$yform = new rex_yform();
if ('REX_VALUE[7]' == 1) {
    $yform->setDebug(true);
}
$form_data = 'REX_VALUE[id=3 output=html]';
$form_data = trim(rex_yform::unhtmlentities($form_data));
$yform->setObjectparams('form_action', rex_getUrl(REX_ARTICLE_ID, REX_CLANG_ID));
$yform->setFormData($form_data);

// action - showtext
if ('REX_VALUE[id=6]' != '') {
    $html = '0'; // plaintext
    if ('REX_VALUE[11]' == 1) {
        $html = '1'; // html
    }

    $e3 = '';
    $e4 = '';
    if ($html == '0') {
        $e3 = '<div class="alert alert-success">';
        $e4 = '</div>';
    }

    $t = str_replace('<br />', '', rex_yform::unhtmlentities('REX_VALUE[6]'));
    $yform->setActionField('showtext', [$t, $e3, $e4, $html]);
}

$form_type = 'REX_VALUE[1]';

// action - email
if ($form_type == '1' || $form_type == '2') {
    $mail_from = ('REX_VALUE[2]' != '') ? 'REX_VALUE[2]' : rex::getErrorEmail();
    $mail_to = ('REX_VALUE[12]' != '') ? 'REX_VALUE[12]' : rex::getErrorEmail();
    $mail_subject = 'REX_VALUE[4]';
    $mail_body = str_replace('<br />', '', rex_yform::unhtmlentities('REX_VALUE[5]'));
    $yform->setActionField('email', [$mail_from, $mail_to, $mail_subject, $mail_body]);
}

// action - db
if ($form_type == '0' || $form_type == '2') {
    $yform->setObjectparams('main_table', 'REX_VALUE[8]');

    if ('REX_VALUE[10]' != '') {
        $yform->setObjectparams('getdata', true);
    }

    $yform->setActionField('db', ['REX_VALUE[8]', $yform->objparams['main_where']]);
}

echo $yform->getForm();
