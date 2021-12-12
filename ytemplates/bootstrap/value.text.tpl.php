<?php

/**
 * @var rex_yform_value_text $this
 * @psalm-scope-this rex_yform_value_text
 */

$type = $type ?? 'text';
$class = 'text' == $type ? '' : 'form-' . $type . ' ';
if (!isset($value)) {
    $value = $this->getValue();
}

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()]) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class_group = [];
$class_group['form-group'] = 'form-group';
if (!empty($this->getWarningClass())) {
    $class_group[$this->getWarningClass()] = $this->getWarningClass();
}

$class_label[] = 'control-label';

$attributes = [
    'class' => 'form-control',
    'name' => $this->getFieldName(),
    'type' => $type,
    'id' => $this->getFieldId(),
    'value' => $value,
];

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

$input_group_start = '';
$input_group_end = '';

$prepend_view = '';
if (!empty($prepend)) {
    $prepend_view = '<span class="input-group-addon">'.$prepend.'</span>';
    $input_group_start = '<div class="input-group">';
    $input_group_end = '</div>';
}

$append_view = '';
if (!empty($append)) {
    $append_view = '<span class="input-group-addon">'.$append.'</span>';
    $input_group_start = '<div class="input-group">';
    $input_group_end = '</div>';
}

echo '<div class="'.implode(' ', $class_group).'" id="'.$this->getHTMLId().'">
        <label class="'.implode(' ', $class_label).'" for="'.$this->getFieldId().'">'.$this->getLabel().'</label>
        ' . $input_group_start . $prepend_view . '<input '.implode(' ', $attributes).' />' . $append_view . $input_group_end . $notice .'
        </div>';

?>

