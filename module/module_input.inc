<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// module:yform_basic_input
// v1.2
// --------------------------------------------------------------------------------

// DEBUG SELECT
////////////////////////////////////////////////////////////////////////////////
$dbg_sel = new rex_select();
$dbg_sel->setName('REX_INPUT_VALUE[7]');
$dbg_sel->setAttribute('class', 'form-control');
$dbg_sel->addOption('inaktiv','0');
$dbg_sel->addOption('aktiv','1');
$dbg_sel->setSelected('REX_VALUE[7]');
$dbg_sel = $dbg_sel->get();


// TABLE SELECT
////////////////////////////////////////////////////////////////////////////////
$gc = rex_sql::factory();
$gc->setQuery('SHOW TABLES');
$tables = $gc->getArray();
$tbl_sel = new rex_select();
$tbl_sel->setName('REX_INPUT_VALUE[8]');
$tbl_sel->setAttribute('class', 'form-control');
$tbl_sel->addOption('Keine Tabelle ausgewählt', '');
foreach ($tables as $key => $value) {
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
if('REX_VALUE[3]'!='') {
  $ignores = array('html','validate','action');
  $placeholders .= '
        <div id="yform-js-formbuilder-placeholders">
            <h3>Platzhalter: <span>[<a href="#" id="yform-js-formbuilder-placeholders-help-toggler">?</a>]</span></h3>'.PHP_EOL;
  foreach($yform->objparams['form_elements'] as $e) {
    if(!in_array($e[0],$ignores) && isset($e[1])) {
      $label = (isset($e[2]) && $e[2] != '') ? $e[2] . ': ' : '';
      $placeholders .= '<code>' . $label . '###'.$e[1].'###</code> '.PHP_EOL;
    }
  }
  $placeholders .= '
            <ul id="yform-js-formbuilder-placeholders-help">
                <li>Die Platzhalter ergeben sich aus den obenstehenden Felddefinitionen.</li>
                <li>Per Klick können einzelne Platzhalter in den Mail-Body kopiert werden.</li>
                <li>Aktualisierung der Platzhalter erfolgt über die Aktualisierung des Moduls.</li>
            </ul>
        </div>'.PHP_EOL;
}


// OTHERS
////////////////////////////////////////////////////////////////////////////////
$row_pad = 1;

$sel = 'REX_VALUE[1]';
$db_display   = ($sel=='' || $sel==1) ?' style="display:none"':'';
$mail_display = ($sel=='' || $sel==0) ?' style="display:none"':'';

?>

<div id="yform-formbuilder">
  <fieldset class="form-horizontal">
    <legend>Formular</legend>
    <div class="form-group">
      <label class="col-md-2 control-label text-left">Debug Modus</label>
      <div class="col-md-10">
        <div class="yform-select-style">
          <?= $dbg_sel; ?>
        </div>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label" for="yform-formbuilder-definition">Felddefinitionen</label>
      <div class="col-md-10">
        <textarea class="form-control" style="font-family: monospace;" id="yform-formbuilder-definition" name="REX_INPUT_VALUE[3]" rows="<?php echo (count(explode("\r",'REX_VALUE[3]'))+$row_pad);?>">REX_VALUE[3]</textarea>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label">Verfügbare Feldklassen</label>
      <div class="col-md-10">
        <div id="yform-formbuilder-classes-showhelp"><?= rex_yform::showHelp(); ?></div>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label">Meldung bei erfolgreichen Versand</label>
      <div class="col-md-10">
        <label class="radio-inline">
          <input type="radio" name="REX_INPUT_VALUE[11]" value="0"<?php if("REX_VALUE[11]" == '0') echo ' checked'; ?> /> Plaintext
        </label>
        <label class="radio-inline">
          <input type="radio" name="REX_INPUT_VALUE[11]" value="1"<?php if("REX_VALUE[11]" == '1') echo ' checked'; ?> /> HTML
        </label>
      </div>
      <div class="col-md-offset-2 col-md-10">
        <textarea class="form-control" name="REX_INPUT_VALUE[6]" rows="<?php echo (count(explode("\r",'REX_VALUE[6]'))+$row_pad);?>">REX_VALUE[6]</textarea>
      </div>
    </div>
  </fieldset>

  <fieldset class="form-horizontal">
    <legend>Vordefinierte Aktionen</legend>

    <div class="form-group">
      <label class="col-md-2 control-label">Bei Submit</label>
      <div class="col-md-10">
        <div class="yform-select-style">
          <select class="form-control" id="yform-js-formbuilder-action-select" name="REX_INPUT_VALUE[1]" size="1">
            <option value=""<?php if("REX_VALUE[1]" == "")  echo " selected "; ?>>Nichts machen (actions im Formular definieren)</option>
            <option value="0"<?php if("REX_VALUE[1]" == "0") echo " selected "; ?>>Nur in Datenbank speichern</option>
            <option value="1"<?php if("REX_VALUE[1]" == "1") echo " selected "; ?>>Nur E-Mail versenden</option>
            <option value="2"<?php if("REX_VALUE[1]" == "2") echo " selected "; ?>>E-Mail versenden und in Datenbank speichern</option>
          </select>
        </div>
      </div>
    </div>
  </fieldset>

  <fieldset class="form-horizontal" id="yform-js-formbuilder-mail-fieldset"<?php echo $mail_display;?> >
    <legend>E-Mail-Versand:</legend>

    <div class="form-group">
      <label class="col-md-2 control-label">Absender</label>
      <div class="col-md-10">
        <input class="form-control" type="text" name="REX_INPUT_VALUE[2]" value="REX_VALUE[2]" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label">Empfänger</label>
      <div class="col-md-10">
        <input class="form-control" type="text" name="REX_INPUT_VALUE[12]" value="REX_VALUE[12]" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label">Subject</label>
      <div class="col-md-10">
        <input class="form-control" type="text" name="REX_INPUT_VALUE[4]" value="REX_VALUE[4]" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label">Mailbody</label>
      <div class="col-md-10">
        <textarea class="form-control" id="yform-js-formbuilder-mail-body" name="REX_INPUT_VALUE[5]" rows="<?php echo (count(explode("\r",'REX_VALUE[5]'))+$row_pad);?>">REX_VALUE[5]</textarea>
        <div class="help-block">
          <?php echo $placeholders;?>
        </div>
      </div>
    </div>

  </fieldset>

  <fieldset class="form-horizontal" id="yform-js-formbuilder-db-fieldset"<?php echo $db_display;?> >
    <legend>Datenbank Einstellungen</legend>

    <div class="form-group">
      <label class="col-md-2 control-label">Tabelle wählen <span>[<a href="#" id="yform-js-formbuilder-db-help-toggler">?</a>]</span></label>
      <div class="col-md-10">
        <div class="yform-select-style">
          <?= $tbl_sel; ?>
        </div>
        <div class="help-block">
          <ul id="yform-js-formbuilder-db-help">
            <li>Hier werden die Daten des Formular hineingespeichert</li>
          </ul>
        </div>
      </div>
    </div>
  </fieldset>

</div>

<p id="yform-formbuilder-info"><?=  rex_addon::get('yform')->getName() . ' ' . rex_addon::get('yform')->getVersion() ?></p>

<script type="text/javascript" nonce="<?php echo rex_response::getNonce(); ?>">
  <!--
  (function($){

    // AUTOGROW BY ROWS
    $('#yform-formbuilder textarea').keyup(function(){
      var rows = $(this).val().split(/\r?\n|\r/).length + <?php echo $row_pad;?>;
      $(this).attr('rows',rows);
    });

    // TOGGLERS
    $('#yform-js-formbuilder-placeholders-help-toggler').click(function(e){
      e.preventDefault();
      $('#yform-js-formbuilder-placeholders-help').toggle(50);return false;
    });
    $('#yform-js-formbuilder-where-help-toggler').click(function(e){
      e.preventDefault();
      $('#yform-js-formbuilder-where-help').toggle(50);return false;
    });
    $('#yform-js-formbuilder-db-help-toggler').click(function(e){
      e.preventDefault();
      $('#yform-js-formbuilder-db-help').toggle(50);return false;
    });


    // INSERT PLACEHOLDERS
    $('#yform-js-formbuilder-placeholders code').click(function(){
      newval = $('#yform-js-formbuilder-mail-body').val()+' '+$(this).html();
      $('#yform-js-formbuilder-mail-body').val(newval);
      $(this).addClass('text-muted');
    });

    // TOGGLE MAIL/DB PANELS
    $('#yform-js-formbuilder-action-select').change(function(){
      switch($(this).val()){
        case '':
          $('#yform-js-formbuilder-db-fieldset').hide(0);
          $('#yform-js-formbuilder-mail-fieldset').hide(0);
          break;
        case '1':
          $('#yform-js-formbuilder-db-fieldset').hide(0);
          $('#yform-js-formbuilder-mail-fieldset').show(0);
          break;
        case '0':
          $('#yform-js-formbuilder-db-fieldset').show(0);
          $('#yform-js-formbuilder-mail-fieldset').hide(0);
          break;
        case '2':
        case '3':
          $('#yform-js-formbuilder-db-fieldset').show(0);
          $('#yform-js-formbuilder-mail-fieldset').show(0);
          break;
      }
    });

  })(jQuery)
  //-->
</script>
