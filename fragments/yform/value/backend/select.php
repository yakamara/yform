<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$multiple ??= false;
$size ??= 1;
$options ??= [];

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
$class_group = trim('form-group ' . $class . $yfield->getWarningClass());

$class_label[] = 'control-label';

$attributes = [];
$attributes['class'] = 'form-control';
$attributes['id'] = $yfield->getFieldId();
if ($multiple) {
    $attributes['name'] = $yfield->getFieldName() . '[]';
    $attributes['multiple'] = 'multiple';
} else {
    $attributes['name'] = $yfield->getFieldName();
}
if ($size > 1) {
    $attributes['size'] = $size;
}

$attributes = $yfield->getAttributeElements($attributes, ['autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

echo '
<div class="' . $class_group . '" id="' . $yfield->getHTMLId() . '">
    <label class="' . implode(' ', $class_label) . '" for="' . $yfield->getFieldId() . '">' . $yfield->getLabel() . '</label>
    <select ' . implode(' ', $attributes) . '>';
foreach ($options as $key => $value):
    echo '<option value="' . \Redaxo\Core\View\escape($key) . '" ';
    if (in_array((string) $key, $yfield->getValue(), true)) {
        echo ' selected="selected"';
    }
    echo '>';
    echo $yfield->getLabelStyle($value);
    echo '</option>';
endforeach;
echo '
    </select>
    ' . $notice . '
</div>';
