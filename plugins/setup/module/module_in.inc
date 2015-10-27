<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// module:yform_basic_in
// v0.2.2
// --------------------------------------------------------------------------------

// DEBUG SELECT
////////////////////////////////////////////////////////////////////////////////
$dbg_sel = new rex_select();
$dbg_sel->setName('VALUE[7]');
$dbg_sel->setSize(1);
$dbg_sel->addOption('inaktiv','0');
$dbg_sel->addOption('aktiv','1');
$dbg_sel->setSelected('REX_VALUE[7]');
$dbg_sel = $dbg_sel->get();


// TABLE SELECT
////////////////////////////////////////////////////////////////////////////////
$gc = rex_sql::factory();
$gc->setQuery('SHOW TABLES');
$tables = $gc->getArray();
$tbl_sel = new rex_select;
$tbl_sel->setName('VALUE[8]');
$tbl_sel->setSize(1);
$tbl_sel->addOption('Keine Tabelle ausgewählt', '');
foreach ($tables as $key => $value)
{
  $tbl_sel->addOption(current($value), current($value));
}
$tbl_sel->setSelected('REX_VALUE[8]');
$tbl_sel = $tbl_sel->get();


// PLACEHOLDERS
////////////////////////////////////////////////////////////////////////////////
$yform = new rex_yform;
$form_data = 'REX_VALUE[3]';
$form_data = trim(str_replace('<br />','',rex_yform::unhtmlentities($form_data)));
$yform->setFormData($form_data);
$yform->setRedaxoVars(REX_ARTICLE_ID,REX_CLANG_ID);
$placeholders = '';
if('REX_VALUE[3]'!='')
{
$ignores = array('html','validate','action');
  $placeholders .= '  <strong class="hint">Platzhalter: <span>[<a href="#" id="yform-placeholders-help-toggler">?</a>]</span></strong>
  <p id="yform-placeholders">'.PHP_EOL;
foreach($yform->objparams['form_elements'] as $e)
{
  if(!in_array($e[0],$ignores) && isset($e[1]))
  {
      $placeholders .= '<span>###'.$e[1].'###</span> '.PHP_EOL;
  }
}
  $placeholders .= '  </p>'.PHP_EOL;
}


// OTHERS
////////////////////////////////////////////////////////////////////////////////
$row_pad = 1;

$sel = 'REX_VALUE[1]';
$db_display   = ($sel=='' || $sel==1) ?'style="display:none"':'';
$mail_display = ($sel=='' || $sel==0) ?'style="display:none"':'';

?>

<style type="text/css" media="screen">
  /*BAISC MODUL STYLE*/
  #yform-modul                       {margin:0;padding:0;line-height:25px;}
  #yform-modul fieldset              {background:#E4E1D1;margin:-20px 0 0 0;padding: 4px 10px 10px 10px;-moz-border-radius:6px;-webkit-border-radius:6px;border-radius:6px;}
  #yform-modul fieldset legend       {display:block !important;position:relative !important;height:auto !important;top:0 !important;left:0 !important;width:100% !important;margin:0 0 0 0 !important;padding:30px 0 0 0px !important;background:transparent !important;border-bottom:1px solid #B1B1B1 !important;color:gray;font-size:14px;font-weight:bold;}
  #yform-modul fieldset legend em    {font-size:10px;font-weight:normal;font-style:normal;}
  #yform-modul fieldset strong.label,
  #yform-modul fieldset label        {display:inline-block !important;width:150px !important;font-weight:bold;}
  #yform-modul fieldset label span   {font-weight:normal;}
  #yform-modul input,
  #yform-modul select                {width:460px;border:auto;margin:0 !important;padding:0 !important;}
  #yform-modul input[type="checkbox"]{width:auto;}
  #yform-modul hr                    {border:0;height:0;margin:4px 0 4px 0;padding:0;border-top:1px solid #B1B1B1 !important;clear:left;}
  #yform-modul a.blank               {background:url("../files/addons/be_style/plugins/agk_skin/popup.gif") no-repeat 100% 0;padding-right:17px;}
  #yform-modul #modulinfo            {font-size:10px;text-align:right;}
  /*yform MODUL*/
  #yform-modul textarea              {min-height:50px;font-family:monospace;font-size:12px;}
  #yform-modul pre                   {clear:left;}
  #yform-modul strong span           {font-weight:normal;}
  #yform-modul .help                 {display:none;color:#2C8EC0;line-height:12px;}
  #yform-modul .area-wrapper         {background:white;border:1px solid #737373;margin-bottom:10px;width:100%;}
  #yform-modul .fullwidth            {width:100% !important;}
  #yform-modul #thx-markup           {width:auto !important;}
  #yform-modul #thx-markup input     {width:auto !important;}
  #yform-modul #yform-placeholders-help,
  #yform-modul #yform-where-help     {display:none;}
  #yform-modul #yform-placeholders,
  #yform-modul #yform-classes-showhelp {background:white;border:1px solid #737373;margin-bottom:10px;width:100%;}
  #yform-modul #yform-placeholders {padding:4px 10px;float:none;width:auto;}
  #yform-modul #yform-placeholders span:hover {color:red;cursor:pointer;}
  #yform-modul em.hint               {color:silver;margin:0;padding:0 0 0 10px;}
  /*SHOWHELP OVERRIDES*/
  #yform-modul ul.yform.root         {border:0;outline:0;margin:4px 0;padding:0;width:100%;background:transparent;}
  #yform-modul ul.yform              {font-size:1.1em;line-height:1.4em;}
</style>


<div id="yform-modul">
<fieldset>
  <legend>Formular</legend>

  <label>DebugModus:</label>
  <?php echo $dbg_sel;?>

  <hr />

  <label class="fullwidth">Felddefinitionen:</label>
  <textarea name="VALUE[3]" id="yform-form-definition" class="fullwidth" rows="<?php echo (count(explode("\r",'REX_VALUE[3]'))+$row_pad);?>">REX_VALUE[3]</textarea>

  <strong class="label">Verfügbare Feld-Klassen:</strong>
  <div id="yform-classes-showhelp">
    <?php echo rex_yform::showHelp(true,true); ?>
  </div><!-- #yform-classes-showhelp -->

  <div id="thx-markup"><strong>Meldung bei erfolgreichen Versand:</strong> (
    <input type="radio" name="VALUE[11]" value="0" <?php if("REX_VALUE[11]" == "0") echo 'checked="checked"'; ?>> Plaintext
    <input type="radio" name="VALUE[11]" value="1" <?php if("REX_VALUE[11]" == "1") echo 'checked="checked"'; ?>> HTML
    <input type="radio" name="VALUE[11]" value="2" <?php if("REX_VALUE[11]" == "2") echo 'checked="checked"'; ?>> Textile)
  </div><!-- #thx-markup -->
  <textarea name="VALUE[6]" id="yform-thx-message" class="fullwidth" rows="<?php echo (count(explode("\r",'REX_VALUE[6]'))+$row_pad);?>">REX_VALUE[6]</textarea>

</fieldset>


<fieldset>
  <legend>Vordefinierte Aktionen</legend>

  <label>Bei Submit:</label>
  <select name="VALUE[1]" id="yform-action-select" style="width:auto;">
    <option value=""  <?php if("REX_VALUE[1]" == "")  echo " selected "; ?>>Nichts machen (actions im Formular definieren)</option>
    <option value="0" <?php if("REX_VALUE[1]" == "0") echo " selected "; ?>>Nur in Datenbank speichern oder aktualisieren wenn "main_where" gesetzt ist</option>
    <option value="1" <?php if("REX_VALUE[1]" == "1") echo " selected "; ?>>Nur E-Mail versenden</option>
    <option value="2" <?php if("REX_VALUE[1]" == "2") echo " selected "; ?>>E-Mail versenden und in Datenbank speichern</option>
    <!--  <option value="3" <?php if("REX_VALUE[1]" == "3") echo " selected "; ?>>E-Mail versenden und Datenbank abfragen</option> -->
  </select>

</fieldset>


<fieldset id="yform-mail-fieldset" <?php echo $mail_display;?> >
  <legend>Emailversand:</legend>

  <label>Absender:</label>
  <input type="text" name="VALUE[2]" value="REX_VALUE[2]" />

  <label>Empfänger:</label>
  <input type="text" name="VALUE[12]" value="REX_VALUE[12]" />

  <label>Subject:</label>
  <input type="text" name="VALUE[4]" value="REX_VALUE[4]" />
  <label class="fullwidth">Mailbody:</label>
  <textarea id="yform-mail-body" class="fullwidth" name="VALUE[5]" rows="<?php echo (count(explode("\r",'REX_VALUE[5]'))+$row_pad);?>">REX_VALUE[5]</textarea>

    <?php echo $placeholders;?>

  <ul class="help" id="yform-placeholders-help">
    <li>Die Platzhalter ergeben sich aus den obenstehenden Felddefinitionen.</li>
    <li>Per click können einzelne Platzhalter in den Mail-Body kopiert werden.</li>
    <li>Aktualisierung der Platzhalter erfolgt über die Aktualisierung des Moduls.</li>
  </ul>


</fieldset>


<fieldset id="yform-db-fieldset" <?php echo $db_display;?> >
  <legend>Datenbank Einstellungen</legend>

  <label>Tabelle wählen <span>[<a href="#" id="yform-db-help-toggler">?</a>]</span></label>
  <?php echo $tbl_sel;?>
  <ul class="help" id="yform-db-select-help">
    <li>Diese Tabelle gilt auch bei Uniqueabfragen (Pflichtfeld=2) siehe oben</li>
  </ul>

  <hr />

  <label for="getdatapre">Daten initial aus DB holen</label>
  <input id="getdatapre" type="checkbox" value="1" name="VALUE[10]" <?php if("REX_VALUE[10]" != "") echo 'checked="checked"'; ?> />

  <div id="db_data">
    <hr />
    <label>Where Klausel: <span>[<a href="#" id="yform-yform-where-help-toggler">?</a>]</span></label>
    <textarea name="VALUE[9]" cols="30" id="yform-db-where" class="fullwidth"rows="<?php echo (count(explode("\r",'REX_VALUE[9]'))+$row_pad);?>">REX_VALUE[9]</textarea>
    <ul class="help" id="yform-where-help">
      <li>PHP erlaubt. Beispiel: <em>$yform-&gt;setObjectparams("main_where",$where);</em></li>
      <li>Die Benutzereingaben aus dem Formular können mittels Platzhaltern (Schema: ###<em>FELDNAME</em>###) in der WHERE Klausel verwendet werden - Beispiel: text|myname|Name|1 -> Platzhalter: ###myname###</li>
    </ul>
  </div><!-- #db_data -->

  </fieldset>

  <p id="modulinfo">yform Formbuilder v0.2.2</p>

</div><!-- #yform-modul -->

<script type="text/javascript">
<!--
(function($){

  // FIX WEBKIT CSS QUIRKS
  if ($.browser.webkit) {
    $('#yform-modul textarea').css('min-height','70px');
    $('#yform-modul textarea').css('width','701px');
    $('#yform-modul fieldset').css('width','705px');
  }

  // AUTOGROW BY ROWS
  $('#yform-modul textarea').keyup(function(){
    var rows = $(this).val().split(/\r?\n|\r/).length + <?php echo $row_pad;?>;
    $(this).attr('rows',rows);
  });

  // TOGGLERS
  $('#yform-placeholders-help-toggler').click(function(){
    $('#yform-placeholders-help').toggle(50);return false;
  });
  $('#yform-yform-where-help-toggler').click(function(){
    $('#yform-where-help').toggle(50);return false;
  });
  $('#yform-db-help-toggler').click(function(){
    $('#yform-db-select-help').toggle(50);return false;
  });


  // INSERT PLACEHOLDERS
  $('#yform-placeholders span').click(function(){
    newval = $('#yform-mail-body').val()+' '+$(this).html();
    $('#yform-mail-body').val(newval);
  });

  // TOGGLE MAIL/DB PANELS
  $('#yform-action-select').change(function(){
    switch($(this).val()){
      case '':
        $('#yform-db-fieldset').hide(0);
        $('#yform-mail-fieldset').hide(0);
        break;
      case '1':
        $('#yform-db-fieldset').hide(0);
        $('#yform-mail-fieldset').show(0);
        break;
      case '0':
        $('#yform-db-fieldset').show(0);
        $('#yform-mail-fieldset').hide(0);
        break;
      case '2':
      case '3':
        $('#yform-db-fieldset').show(0);
        $('#yform-mail-fieldset').show(0);
        break;
    }
  });

})(jQuery)
//-->
</script>