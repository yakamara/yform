<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
 */

$options = $options ?? [];

$notices = [];
if ('' != $this->getElement('notice')) {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notices) . '</p>';
}

$class_label = '';
$class = $this->getElement('required') ? 'form-is-required ' : '';
$class_group = trim('radio-group form-group ' . $class . $this->getWarningClass());

if ('' != trim($this->getLabel())) {
    echo '<div class="'.$class_group.'">
    <label class="control-label'.$class_label.'">'.$this->getLabel().'</label>';
}

foreach ($options as $key => $value) {
    echo '<div class="radio';
    echo (bool) $this->getElement('inline') ? '-inline' : '';
    echo '' == trim($this->getLabel()) ? $this->getWarningClass() : '';
    echo '">';

    $attributes = [
        'id' => $this->getFieldId() . '-' . htmlspecialchars($key),
        'name' => $this->getFieldName(),
        'value' => $key,
        'type' => 'radio',
    ];

    if ($key == $this->getValue()) {
        $attributes['checked'] = 'checked';
    }

    $attributes = $this->getAttributeElements($attributes);

    echo '  <label>
            <input '.implode(' ', $attributes).' />
            '.$this->getLabelStyle($value).'
        </label>
    </div>';
}

echo $notice;

if ('' != trim($this->getLabel())) {
    echo '</div>';
}
