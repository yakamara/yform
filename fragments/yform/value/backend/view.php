<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$value ??= $yfield->getValue() ?? '';
$options ??= [];
$download_link ??= '';

if ('' != $download_link) {
    $filename ??= 'Download';
    $value = '<a href="' . $download_link . '">' . \Redaxo\Core\View\escape($filename) . '</a>';
} elseif (is_array($value)) {
    if (0 == count($value)) {
        $value = '-';
    } elseif (1 == count($value)) {
        $value = (isset($options[current($value)]) ? \Redaxo\Core\View\escape($options[current($value)]) : 'error - no option found for ' . \Redaxo\Core\View\escape(current($value)));
    } else {
        // -> 1 < count($value))
        foreach ($value as $k => $v) {
            $v = (isset($options[$v]) ? \Redaxo\Core\View\escape($options[$v]) : 'error - no option found for ' . \Redaxo\Core\View\escape($v));
            $value[$k] = '<li>' . \Redaxo\Core\View\escape($v) . '</li>';
        }
        $value = '<ul>' . implode('', $value) . '</ul>';
    }
} else {
    $length = mb_strlen($value);
    $title = $value;
    $maxsize = 400;
    if ($length > $maxsize) {
        $value = mb_substr($value, 0, (int) ($maxsize / 2)) . ' ... ' . mb_substr($value, (int) (-($maxsize / 2)));
    }
    $value = \Redaxo\Core\View\escape($value);
}

$class_group = [];
$class_group['form-group'] = 'form-group';
if (!empty($yfield->getWarningClass())) {
    $class_group[$yfield->getWarningClass()] = $yfield->getWarningClass();
}

$notice ??= '';
if ('' != $notice) {
    $notice = '<p class="help-block small">' . \Redaxo\Core\Translation\I18n::translate($notice, false) . '</p>';
}

$class_label[] = 'control-label';

echo '
    <div class="' . implode(' ', $class_group) . '" id="' . $yfield->getHTMLId() . '">
        <label class="' . implode(' ', $class_label) . '" for="' . $yfield->getFieldId() . '">' . $yfield->getLabel() . '</label>
        <div>' . $value . '</div>
        ' . $notice . '
    </div>';
