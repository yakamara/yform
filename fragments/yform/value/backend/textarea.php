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

$class = $yfield->getElement('required') ? 'form-is-required ' : '';
$class_group = trim('form-group ' . $yfield->getWarningClass());
$class_label = ['control-label'];

$rows = $yfield->getElement('rows');
if (!$rows) {
    $rows = 10;
}

$attributes = [
    'class' => 'form-control',
    'name' => $yfield->getFieldName(),
    'id' => $yfield->getFieldId(),
    'rows' => $rows,
];

$attributes = $yfield->getAttributeElements($attributes, ['placeholder', 'pattern', 'required', 'disabled', 'readonly']);

echo '<div class="' . $class_group . '" id="' . $yfield->getHTMLId() . '">
<label class="' . implode(' ', $class_label) . '" for="' . $yfield->getFieldId() . '">' . $yfield->getLabel() . '</label>
<textarea ' . implode(' ', $attributes) . '>' . \Redaxo\Core\View\escape($yfield->getValue()) . '</textarea>' . $notice .
'</div>';
