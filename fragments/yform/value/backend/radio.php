<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$options ??= [];

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

$class_label = '';
$class = $yfield->getElement('required') ? 'form-is-required ' : '';
$class_group = trim('radio-group form-group ' . $class . $yfield->getWarningClass());

if ('' != trim($yfield->getLabel())) {
    echo '<div class="' . $class_group . '">
    <label class="control-label' . $class_label . '">' . $yfield->getLabel() . '</label>';
}

foreach ($options as $key => $value) {
    echo '<div class="radio';
    echo (bool) $yfield->getElement('inline') ? '-inline' : '';
    echo '' == trim($yfield->getLabel()) ? $yfield->getWarningClass() : '';
    echo '">';

    $attributes = [
        'id' => $yfield->getFieldId() . '-' . \Redaxo\Core\View\escape($key),
        'name' => $yfield->getFieldName(),
        'value' => $key,
        'type' => 'radio',
    ];

    if ($key == $yfield->getValue()) {
        $attributes['checked'] = 'checked';
    }

    $attributes = $yfield->getAttributeElements($attributes);

    echo '  <label>
            <input ' . implode(' ', $attributes) . ' />
            ' . $yfield->getLabelStyle($value) . '
        </label>
    </div>';
}

echo $notice;

if ('' != trim($yfield->getLabel())) {
    echo '</div>';
}
