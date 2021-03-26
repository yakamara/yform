<?php

$value = $value ?? $this->getValue() ?? '';

if (is_array($value)) {
    foreach ($value as $k => $v) {
        $value[$k] = '<li>'.rex_escape($v).'</li>';
    }
    $value = '<ul>'.implode('', $value).'</ul>';
} else {
    $length = strlen($value);
    $title = $value;
    $maxsize = 400;
    if ($length > $maxsize) {
        $value = mb_substr($value, 0, $maxsize / 2).' ... '.mb_substr($value, -($maxsize / 2));
    }
    $value = rex_escape($value);
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

echo '
    <div class="'.implode(' ', $class_group).'" id="'.$this->getHTMLId().'">
        <label class="'.implode(' ', $class_label).'" for="'.$this->getFieldId().'">'.$this->getLabel().'</label>
        <div>' . $value . ' ' . $notice . '</div>
    </div>';

?>

