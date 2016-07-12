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

$class_group = trim('form-group yform-element ' . $this->getHTMLClass());

?>
<div class="<?= $class_group ?>"  id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label"><?php echo $this->getLabel() ?></label>
    <p class="form-control-static"><?php echo htmlspecialchars($this->getValue()) ?></p>
    <input type="hidden" name="<?php echo $this->getFieldName() ?>" value="<?php echo htmlspecialchars($this->getValue()) ?>" />
    <?php echo $notice ?>
</div>
