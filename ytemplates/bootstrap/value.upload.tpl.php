<?php

$notice = [];
if ($this->getElement('notice') != '') {
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


$class  = $this->getElement('required') ? 'form-is-required ' : '';

$class_group   = trim('form-group ' . $class . $this->getWarningClass());
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
    <?php echo $field_before; ?><input class="<?php echo $class_control ?>" id="<?php echo $this->getFieldId() ?>" type="file" name="file_<?php echo md5($this->getFieldName('file')) ?>" />
    <?php echo $notice ?><?php echo $field_after; ?>
</div>

<?php

$value = $this->getValue();
if ($value != '') {
    $values = explode('_', $value, 2);
    if (count($values) == 2) {
        echo '<input type="hidden" name="' . $this->getFieldName() . '" value="' . $values[0] . '" />';

        $label = htmlspecialchars($values[1]);

        if (rex::isBackend()) {
            $label = '<a href="' . $_SERVER["REQUEST_URI"] . '&rex_upload_downloadfile=' . urlencode($this->getValue()) . '">' . $label . '</a>';
        }

        echo '
        <div class="checkbox" id="' . $this->getHTMLId('checkbox') . '">
            <label>
                <input type="checkbox" id="' .  $this->getFieldId(delete) . '" name="' . $this->getFieldName('delete') . '" value="1" />
                ' . $this->tmp_messages['delete_file'] . ' "' . $label . '"
            </label>
        </div>';

    }
}

?>
