<?php

$value = $value ?? $this->getValue() ?? '';

if (is_array($value)) {
    if (0 == count($value)) {
        $value = '-';
    } elseif (1 == count($value)) {
        $value = rex_escape(current($value));
    } elseif (1 < count($value)) {
        foreach ($value as $k => $v) {
            $value[$k] = '<li>'.rex_escape($v).'</li>';
        }
        $value = '<ul>'.implode('', $value).'</ul>';
    }

} else {
    $length = strlen($value);
    $title = $value;
    $maxsize = 400;
    if ($length > $maxsize) {
        $value = mb_substr($value, 0, $maxsize / 2).' ... '.mb_substr($value, -($maxsize / 2));
    }
    $value = rex_escape($value);
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
        <div>' . $value . '</div>
    </div>';

?>

