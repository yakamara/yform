<?php

$notices = [];
if ($this->getElement('notice') != '') {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], null, false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notices) . '</p>';
}

$class_label = '';
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

if (trim($this->getLabel()) != '') {
    echo '<div class="radio-group form-group">
    <label class="control-label'.$class_label.'">'.$this->getLabel().'</label>';
}

echo $field_before;

foreach ($options as $key => $value) {
    echo '<div class="radio';
    echo (bool) $this->getElement('inline') ? '-inline' : '';
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
echo $field_after;

if (trim($this->getLabel()) != '') {
    echo '</div>';
}
