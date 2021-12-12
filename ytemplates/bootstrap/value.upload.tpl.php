<?php

/**
 * @var rex_yform_value_upload $this
 * @psalm-scope-this rex_yform_value_upload
 */

$unique = $unique ?? '';
$filename = $filename ?? '';
$download_link = $download_link ?? '';
$error_messages = $error_messages ?? [];

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';

$class_group = trim('form-group  ' . $class . $this->getWarningClass());
$class_control = trim('form-control');

?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <input class="<?php echo $class_control ?>" id="<?php echo $this->getFieldId() ?>" type="file" accept="<?php echo $this->getElement('types') ?>" name="<?php echo $unique ?>" />
    <?php echo $notice ?>
    <input type="hidden" name="<?php echo $this->getFieldName('unique'); ?>" value="<?php echo rex_escape($unique, 'html'); ?>" />
</div>

<?php

if ('' != $filename) {
    $label = htmlspecialchars($filename);

    if (rex::isBackend() && '' != $download_link) {
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
