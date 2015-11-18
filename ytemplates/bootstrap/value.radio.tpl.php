<?php
$notices = array();
if ($this->getElement('notice') != "") {
    $notices[] = $this->getElement('notice');
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] =  '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block">' . implode("<br />", $notices) . '</p>';
}


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


<?php if (trim($this->getLabel()) != ''): ?>
    <div class="form-group">
    <label class="control-label<?php echo $class_label; ?>"><?php echo $this->getLabel() ?></label>
<?php endif; ?>
<?php echo $field_before; ?>
<?php
foreach ($options as $key => $value): ?>
    <?php $id = $this->getFieldId() . '-' . htmlspecialchars($key) ?>
    <div class="radio<?php echo (bool)$this->getElement('inline') ? '-inline' : ''; ?>">
        <label>
            <input type="radio" id="<?php echo $id ?>" name="<?php echo $this->getFieldName() ?>" value="<?php echo htmlspecialchars($key) ?>"<?php echo $key == $this->getValue() ? ' checked="checked"' : '' ?> />
            <?php echo $this->getLabelStyle($value) ?>
        </label>
    </div>
<?php endforeach ?>
<?php echo $notice; ?>
<?php echo $field_after; ?>
<?php if (trim($this->getLabel()) != ''): ?>
    </div>
<?php endif; ?>
