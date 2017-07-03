<?php

$notice = [];
if ($this->getElement('notice') != '') {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';

$class_group = trim('form-group ' . $class . $this->getWarningClass());
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
    <?php echo $field_before; ?><input class="<?php echo $class_control ?>" id="<?php echo $this->getFieldId() ?>" type="file" name="<?php echo $unique ?>" />
    <?php echo $notice ?><?php echo $field_after; ?>
    <input type="hidden" name="<?php echo $this->getFieldName('unique'); ?>" value="<?php echo $unique; ?>" />
</div>

<?php

$value = $this->getValue();
if ($filename != '') {
    $label = htmlspecialchars($filename);

        if (rex::isBackend() && $download_link != "") {
            $label = '<a href="' . $download_link . '">' . $label . '</a>';

        }

        echo '
        <div class="checkbox" id="' . $this->getHTMLId('checkbox') . '">
            <label>
                <input type="checkbox" id="' .  $this->getFieldId('delete') . '" name="' . $this->getFieldName('delete') . '" value="1" />
                ' . $error_messages['delete_file'] . ' "' . $label . '"
            </label>
        </div>';
}

?>
