<?php

use Yakamara\YForm\Value\ShowValue;

/** @var ShowValue $this */
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

$class_group = trim('form-group ' . $this->getHTMLClass());

?>
<div class="<?= $class_group ?>"  id="<?= $this->getHTMLId() ?>">
    <label class="control-label"><?= $this->getLabel() ?></label>
    <p class="form-control-static"><?= (isset($showValue)) ? nl2br(rex_escape($showValue)) : rex_escape($this->getValue()) ?></p>
    <input type="hidden" name="<?= $this->getFieldName() ?>" value="<?= rex_escape($this->getValue()) ?>" />
    <?= $notice ?>
</div>
