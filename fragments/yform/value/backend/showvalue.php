<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
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

$class_group = trim('form-group ' . $yfield->getHTMLClass());

?>
<div class="<?= $class_group ?>"  id="<?= $yfield->getHTMLId() ?>">
    <label class="control-label"><?= $yfield->getLabel() ?></label>
    <p class="form-control-static"><?= (isset($showValue)) ? nl2br(\Redaxo\Core\View\escape($showValue)) : \Redaxo\Core\View\escape($yfield->getValue()) ?></p>
    <input type="hidden" name="<?= $yfield->getFieldName() ?>" value="<?= \Redaxo\Core\View\escape($yfield->getValue()) ?>" />
    <?= $notice ?>
</div>
