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
<div class="checkbox-group form-group">
    <label class="control-label<?php echo $class_label; ?>"><?php echo $this->getLabel() ?></label>

<?php endif; ?>
<?php echo $field_before; ?>

<?php foreach ($options as $k => $v): ?>
    <?php
    $class_group = trim('checkbox yform-element ' . $this->getHTMLClass($k) . ' ' . $this->getWarningClass());
    ?>
    <div class="<?= $class_group ?>" id="<?= $this->getHTMLId($k) ?>">
        <label>
            <input type="checkbox" name="<?= $this->getFieldName() ?>[]" value="<?= $k ?>"<?= in_array($k, $this->getValue()) ? ' checked="checked"' : '' ?> />
            <?= $this->getLabelStyle($v) ?>
        </label>
    </div>
<?php endforeach ?>
<?php echo $notice; ?>
<?php echo $field_after; ?>
<?php if (trim($this->getLabel()) != ''): ?>
</div>
<?php endif; ?>
