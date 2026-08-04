<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$counter ??= 1;

$buttonId = 'yf_' . uniqid() . '_' . $counter;
$categoryId = 0;
$name = $yfield->getFieldName();
$value = \Redaxo\Core\View\escape($yfield->getValue() ?? '');

if (1 == $yfield->getElement('multiple')) {
    $widget = \Redaxo\Core\RexVar\LinkListVar::getWidget($buttonId, $name, $value);
} else {
    $widget = \Redaxo\Core\RexVar\LinkVar::getWidget($buttonId, $name, ('' === $value || null === $value) ? null : (int) $value);
}

$class_group = trim('form-group ' . $yfield->getHTMLClass() . ' ' . $yfield->getWarningClass());

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

?>
<div class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
    <label class="control-label" for="<?= $yfield->getFieldId() ?>"><?= $yfield->getLabel() ?></label>
    <?= $widget ?>
    <?= $notice ?>
</div>
