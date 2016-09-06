<?php

$notice = array();
if ($this->getElement('notice') != "") {
    $notice[] = $this->getElement('notice');
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] =  '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode("<br />", $notice) . '</p>';

} else {
    $notice = '';
}

$class  = $this->getElement('required') ? 'form-is-required ' : '';
$class_group   = trim('form-group ' . $class . $this->getWarningClass());


$class_label[] = 'control-label';
$field_before = '';
$field_after = '';

if (trim($this->getElement('grid')) != '') {
    $grid = explode(',', trim($this->getElement('grid')));

    if (isset($grid[0]) && $grid[0] != '') {
        $class_label .= ' ' . trim($grid[0]);
    }

    if (isset($grid[1]) && $grid[1] != '') {
        $field_before = '<div class="' . trim($grid[1]) . '">';
        $field_after = '</div>';
    }
}

$attributes = [];
$attributes["class"] = 'form-control';
$attributes["id"] = $this->getFieldId();
if($multiple) {
    $attributes["name"] = $this->getFieldName() . '[]';
    $attributes["multiple"] = "multiple";
} else {
    $attributes["name"] = $this->getFieldName();
}
if ($size > 1) {
    $attributes["size"] = $size;

}

$attributes = $this->getAttributeElements($attributes, ['autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

echo '
<div class="'.$class_group.'" id="'.$this->getHTMLId().'">
    <label class="'.implode(" ", $class_label).'" for="'.$this->getFieldId().'">'.$this->getLabel().'</label>
    '.$field_before.'
    <select '.implode(" ", $attributes).'>';
    foreach ($options as $key => $value):
        echo '<option value="'.htmlspecialchars($key).'" ';
        if ( in_array((string) $key, $this->getValue()) ) echo ' selected="selected"';
        echo '>';
        echo $this->getLabelStyle($value);
        echo '</option>';
        endforeach;
echo '
    </select>
    '.$notice . $field_after.'
</div>';
