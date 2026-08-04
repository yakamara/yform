<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$value ??= $yfield->getValue() ?? '';

$notices = [];
if ('' != $yfield->getElement('notice')) {
    $notices[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()], false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notices) . '</p>';
}

$class_group = trim('checkbox ' . $yfield->getHTMLClass() . ' ' . $yfield->getWarningClass());

$attributes = [
    'type' => 'checkbox',
    'id' => $yfield->getFieldId(),
    'name' => $yfield->getFieldName(),
    'value' => 1,
];
if (1 == $value) {
    $attributes['checked'] = 'checked';
}

$attributes = $yfield->getAttributeElements($attributes, ['required', 'disabled', 'autofocus']);

?>
<div class="<?= $class_group ?>" id="<?= $yfield->getHTMLId() ?>">
<label><input <?= implode(' ', $attributes) ?> /><i class="form-helper"></i><?= $yfield->getLabel() ?></label>
<?= $notice ?>
</div>
