<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$counter ??= 1000;

$buttonId = $counter;
$name = $yfield->getFieldName();
$value = \Redaxo\Core\View\escape($yfield->getValue());
$types ??= $yfield->getElement('types');

$widgetCategory = ('' != $yfield->getElement('category')) ? (int) $yfield->getElement('category') : null;
$widgetPreview = (bool) $yfield->getElement('preview');
$widgetTypes = ('' != $types) ? array_values(array_filter(array_map('trim', explode(',', (string) $types)))) : [];

if (1 == $yfield->getElement('multiple')) {
    $widget = \Redaxo\Core\RexVar\MediaListVar::getWidget($buttonId, $name, $value, $widgetCategory, $widgetTypes, $widgetPreview);
} else {
    $widget = \Redaxo\Core\RexVar\MediaVar::getWidget($buttonId, $name, $value, $widgetCategory, $widgetTypes, $widgetPreview);
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
<div data-be-media-wrapper="<?= $yfield->getFieldName() ?>" class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
    <label class="control-label" for="<?= $yfield->getFieldId() ?>"><?= $yfield->getLabel() ?></label>
    <?= $widget ?>
    <?= $notice ?>
</div>
