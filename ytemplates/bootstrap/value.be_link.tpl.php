<?php

use Yakamara\YForm\Value\BackendLink;

/** @var BackendLink $this */
$counter ??= 1;

$buttonId = 'yf_' . uniqid() . '_' . $counter;
$categoryId = 0;
$name = $this->getFieldName();
$value = rex_escape($this->getValue() ?? '');

if (1 == $this->getElement('multiple')) {
    $widget = rex_var_linklist::getWidget($buttonId, $name, $value, []);
} else {
    $widget = rex_var_link::getWidget($buttonId, $name, $value, []);
}

$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

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

?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
    <?= $widget ?>
    <?= $notice ?>
</div>
