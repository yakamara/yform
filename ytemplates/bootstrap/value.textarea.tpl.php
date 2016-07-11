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

$class = $this->getElement('required') ? 'form-is-required ' : '';

$class_group   = trim('form-group ' . $class . $this->getElement(5) . ' ' . $this->getWarningClass());
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

$rows = $this->getElement('rows');
if ($rows == "") {
    $rows = 10;
}

?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label<?php echo $class_label; ?>" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <?php echo $field_before; ?><textarea class="<?php echo $class_control ?>" name="<?php echo $this->getFieldName() ?>" id="<?php echo $this->getFieldId() ?>" rows="<?php echo $rows; ?>" <?php echo $this->getAttributeElement('placeholder'), $this->getAttributeElement('pattern'), $this->getAttributeElement('required', true), $this->getAttributeElement('disabled', true), $this->getAttributeElement('readonly', true) ?>><?php echo htmlspecialchars($this->getValue()) ?></textarea>
    <?php echo $notice ?><?php echo $field_after; ?>
</div>
