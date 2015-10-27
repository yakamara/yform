<?php

$notice = array();
if ($this->getElement('notice') != "") {
  $notice[] = $this->getElement('notice');
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] =  '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode("<br />", $notice) . '</p>';

} else {
    $notice = '';
}

$value = isset($value) ? $value : 1;
$class_group = trim('form-group ' . $this->getWarningClass());

?>
<div class="checkbox" id="<?php echo $this->getHTMLId() ?>">
    <label>
        <input type="checkbox" id="<?php echo $this->getFieldId() ?>" name="<?php echo $this->getFieldName() ?>" value="<?php echo $value ?>"<?php echo $this->getValue() == $value ? ' checked="checked"' : '' ?> />
        <?php echo $this->getLabel() ?>
        <?php echo $notice; ?>
    </label>
</div>