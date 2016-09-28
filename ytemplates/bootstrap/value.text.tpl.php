<?php

$type = isset($type) ? $type : 'text';
$class = $type == 'text' ? '' : 'form-' . $type . ' ';
if (!isset($value)) {
    $value = $this->getValue();
}

$notice = array();
if ($this->getElement('notice') != "") {
  $notice[] = $this->getElement('notice');
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] =  '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()]) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode("<br />", $notice) . '</p>';

} else {
    $notice = '';
}

$class_group   = trim('form-group yform-element ' . $this->getWarningClass());

$class_label[] = 'control-label';
$field_before = '';
$field_after = '';

if (trim($this->getElement('grid')) != '') {
    $grid = explode(',', trim($this->getElement('grid')));

    if (isset($grid[0]) && $grid[0] != '') {
        $class_label[] = trim($grid[0]);
    }

    if (isset($grid[1]) && $grid[1] != '') {
        $field_before = '<div class="' . trim($grid[1]) . '">';
        $field_after = '</div>';
    }
}

$attributes = [
    "class" => 'form-control',
    "name" => $this->getFieldName(),
    "type" => $type,
    "id" => $this->getFieldId(),
    "value" => $value
];

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

echo '<div class="'.$class_group.'" id="'.$this->getHTMLId().'">
<label class="'.implode(" ", $class_label).'" for="'.$this->getFieldId().'">'.$this->getLabel().'</label>
'.$field_before.'<input '.implode(" ", $attributes).' />'.$notice . $field_after.'
</div>';