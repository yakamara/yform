<?php

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';
$class_group = trim('form-group ' . $this->getWarningClass());
$class_label = ['control-label'];

$rows = $this->getElement('rows');
if (!$rows) {
    $rows = 10;
}

$attributes = [
    'class' => 'form-control',
    'name' => $this->getFieldName(),
    'id' => $this->getFieldId(),
    'rows' => $rows,
];

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'pattern', 'required', 'disabled', 'readonly']);

echo '<div class="'.$class_group.'" id="'.$this->getHTMLId().'">
<label class="'.implode(' ', $class_label).'" for="'.$this->getFieldId().'">'.$this->getLabel().'</label>
<textarea '.implode(' ', $attributes).'>'.htmlspecialchars($this->getValue()).'</textarea>' . $notice .
'</div>';
