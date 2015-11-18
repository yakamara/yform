<?php

$type = isset($type) ? $type : 'text';
$class = $type == 'text' ? '' : 'form-' . $type . ' ';
$value = isset($value) ? $value : stripslashes($this->getValue());

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

$class .= $this->getElement('required') ? 'form-is-required ' : '';

$class_group   = trim('form-group yform-element ' . $class . $this->getElement(5) . ' ' . $this->getWarningClass());
$class_control = trim('form-control');

$class_label = '';
$field_before = '';
$field_after = '';

if (trim($this->getElement('grid')) != '') {
    $grid = explode(',', trim($this->getElement('grid')));

    if (isset($grid[0]) && $grid[0] != '') {
        $class_label .= ' ' . trim($grid[0]);
    }

    if (isset($grid[1]) && $grid[1] != '') {
        $field_before = '<div class="' . trim($grid[1]) . '">';
        $field_after = '</div>';
    }
}
?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label<?php echo $class_label; ?>" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <?php echo $field_before; ?><input class="<?php echo $class_control ?>" type="<?php echo $type ?>" name="<?php echo $this->getFieldName() ?>" id="<?php echo $this->getFieldId() ?>" value="<?php echo htmlspecialchars($value) ?>"<?php echo $this->getAttributeElement('placeholder'), $this->getAttributeElement('autocomplete'), $this->getAttributeElement('pattern'), $this->getAttributeElement('required', true), $this->getAttributeElement('disabled', true), $this->getAttributeElement('readonly', true) ?> />
    <?php echo $notice ?><?php echo $field_after; ?>
</div>
