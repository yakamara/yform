<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$type ??= 'text';
$class = 'text' == $type ? '' : 'form-' . $type . ' ';
if (!isset($value)) {
    $value = $yfield->getValue();
}

$notice = [];
if ('' != $yfield->getElement('notice')) {
    $notice[] = \Redaxo\Core\Translation\I18n::translate($yfield->getElement('notice'), false);
}
if (isset($yfield->params['warning_messages'][$yfield->getId()]) && !$yfield->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . \Redaxo\Core\Translation\I18n::translate($yfield->params['warning_messages'][$yfield->getId()]) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class_group = [];
$class_group['form-group'] = 'form-group';
if (!empty($yfield->getWarningClass())) {
    $class_group[$yfield->getWarningClass()] = $yfield->getWarningClass();
}

$class_label[] = 'control-label';

$attributes = [
    'class' => 'form-control',
    'name' => $yfield->getFieldName(),
    'type' => $type,
    'id' => $yfield->getFieldId(),
    'value' => $value,
];

$attributes = $yfield->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

$input_group_start = '';
$input_group_end = '';

$prepend_view = '';
if (!empty($prepend)) {
    $prepend_view = '<span class="input-group-addon">' . $prepend . '</span>';
    $input_group_start = '<div class="input-group">';
    $input_group_end = '</div>';
}

$append_view = '';
if (!empty($append)) {
    $append_view = '<span class="input-group-addon">' . $append . '</span>';
    $input_group_start = '<div class="input-group">';
    $input_group_end = '</div>';
}

echo '<div class="' . implode(' ', $class_group) . '" id="' . $yfield->getHTMLId() . '">
        <label class="' . implode(' ', $class_label) . '" for="' . $yfield->getFieldId() . '">' . $yfield->getLabel() . '</label>
        ' . $input_group_start . $prepend_view . '<input ' . implode(' ', $attributes) . ' />' . $append_view . $input_group_end . $notice . '
        </div>';
