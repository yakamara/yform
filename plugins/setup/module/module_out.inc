<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// module:yform_basic_out
// v0.2.2
//--------------------------------------------------------------------------------

$yform = new rex_yform;
if ("REX_VALUE[7]" == 1) { $yform->setDebug(TRUE); }
$form_data = 'REX_VALUE[3]';
$form_data = trim(str_replace("<br />","",rex_yform::unhtmlentities($form_data)));
$yform->setObjectparams('form_action', rex_getUrl(REX_ARTICLE_ID,REX_CLANG_ID));
$yform->setFormData($form_data);

?>REX_PHP_VALUE[9]<?php

// action - showtext
if("REX_IS_VALUE[6]" == "true")
{
  $html = "0"; // plaintext
  if('REX_VALUE[11]' == 1) $html = "1"; // html
  if('REX_VALUE[11]' == 2) $html = "2"; // textile
  
  $e3 = ''; $e4 = '';
  if ($html == "0") {
    $e3 = '<div class="rex-message"><div class="rex-info"><p>';
    $e4 = '</p></div></div>';
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
if ($form_type == "1" || $form_type == "2" || $form_type == "3")
{
  $mail_from = $REX['ERROR_EMAIL'];
  if("REX_VALUE[2]" != "") $mail_from = "REX_VALUE[2]";
  $mail_to = $REX['ERROR_EMAIL'];
  if("REX_VALUE[12]" != "") $mail_to = "REX_VALUE[12]";
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
if ($form_type == "0" || $form_type == "2" || $form_type == "3")
{
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
