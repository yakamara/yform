<?php

/**
 * @var rex_yform_value_upload $this
 * @psalm-scope-this rex_yform_value_upload
 */

$unique = $unique ?? '';
$filename = $filename ?? '';
$download_link = $download_link ?? '';
$error_messages = $error_messages ?? [];
$configuration = $configuration ?? [];
$allowed_extensions = $configuration['allowed_extensions'] ?? ['*'];
$allowed_extensions = '*' == $allowed_extensions[0] ? '*' : '.'.implode(',.', $configuration['allowed_extensions']);

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';

$class_group = trim('form-group  ' . $class . $this->getWarningClass());
$class_control = trim('form-control');

$inputAttributes = [
    'class' => $class_control,
    'id' => $this->getFieldId(),
    'type' => 'file',
    'name' => $unique,
    'accept' => $allowed_extensions,
];
$inputAttributes = $this->getAttributeElements($inputAttributes, ['required', 'disabled', 'readonly']);

?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <div class="input-group">
        <input <?php echo implode(' ', $inputAttributes) ?> />
        <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="const file = document.getElementById('<?= $this->getFieldId() ?>'); file.value = '';">&times;</button></span>
    </div>
    <?php echo $notice ?>
    <input type="hidden" name="<?php echo $this->getFieldName('unique'); ?>" value="<?php echo rex_escape($unique, 'html'); ?>" />
</div>

<?php
    if ('' != $filename) {
        $label = rex_escape($filename);

        if (rex::isBackend() && '' != $download_link) {
            $label = '<a href="' . $download_link . '">' . $label . '</a>';
        }

        echo '
            <div class="checkbox" id="' . $this->getHTMLId('checkbox') . '">
                <label>
                    <input type="checkbox" id="' .  $this->getFieldId('delete') . '" name="' . $this->getFieldName('delete') . '" value="1" />
                    ' . ($error_messages['delete_file'] ?? 'delete-file-msg') . ' "' . $label . '"
                </label>
            </div>';
    }
?>
