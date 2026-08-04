<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$unique ??= '';
$filename ??= '';
$download_link ??= '';
$error_messages ??= [];
$configuration ??= [];
$allowed_extensions = $configuration['allowed_extensions'] ?? ['*'];
$allowed_extensions = '*' == $allowed_extensions[0] ? '*' : '.' . implode(',.', $configuration['allowed_extensions']);

$notice = [];
if ('' != $yfield->getElement('notice')) {
    $notice[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class = $yfield->getElement('required') ? 'form-is-required ' : '';

$class_group = trim('form-group  ' . $class . $yfield->getWarningClass());
$class_control = trim('form-control');

$inputAttributes = [
    'class' => $class_control,
    'id' => $yfield->getFieldId(),
    'type' => 'file',
    'name' => $unique,
    'accept' => $allowed_extensions,
];
$inputAttributes = $yfield->getAttributeElements($inputAttributes, ['required', 'disabled', 'readonly']);

?>
<div class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
    <label class="control-label" for="<?= $yfield->getFieldId() ?>"><?= $yfield->getLabel() ?></label>
    <div class="input-group">
        <input <?= implode(' ', $inputAttributes) ?> />
        <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="const file = document.getElementById('<?= $yfield->getFieldId() ?>'); file.value = '';">&times;</button></span>
    </div>
    <?= $notice ?>
    <input type="hidden" name="<?= $yfield->getFieldName('unique') ?>" value="<?= \Redaxo\Core\View\escape($unique, 'html') ?>" />
</div>

<?php
    if ('' != $filename) {
        $label = \Redaxo\Core\View\escape($filename);

        if (\Redaxo\Core\Core::isBackend() && '' != $download_link) {
            $label = '<a href="' . $download_link . '">' . $label . '</a>';
        }

        echo '
            <div class="checkbox" id="' . $yfield->getHTMLId('checkbox') . '">
                <label>
                    <input type="checkbox" id="' . $yfield->getFieldId('delete') . '" name="' . $yfield->getFieldName('delete') . '" value="1" />
                    ' . ($error_messages['delete_file'] ?? 'delete-file-msg') . ' "' . $label . '"
                </label>
            </div>';
    }
?>
