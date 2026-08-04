<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$labels ??= [];

$css_classes = [];
if ('' != $yfield->getElement('css_classes')) {
    $css_classes = explode(',', $yfield->getElement('css_classes'));
}

if (count($labels) > 1) {
    if (\Redaxo\Core\Core::isBackend()) {
        echo '<div class="rex-form-panel-footer">';
    }
    echo '<div class="btn-toolbar">';
}

foreach ($labels as $index => $label) {
    $classes = [];
    $classes[] = 'btn';
    // $classes[] = 'btn-primary';

    if (isset($css_classes[$index]) && '' != trim($css_classes[$index])) {
        $classes[] = trim($css_classes[$index]);
    }

    if ('' != $yfield->getWarningClass()) {
        $classes[] = $yfield->getWarningClass();
    }

    $id = $yfield->getFieldId() . '-' . \Redaxo\Core\Util\Str::normalize($label);
    $label_translated = \Redaxo\Core\Translation\I18n::translate($label, true);

    echo '<button class="' . implode(' ', $classes) . '" type="submit" name="' . $yfield->getFieldName() . '" id="' . $id . '" value="' . \Redaxo\Core\View\escape($label) . '">' . $label_translated . '</button>';
}

if (count($labels) > 1) {
    echo '</div>';
    if (\Redaxo\Core\Core::isBackend()) {
        echo '</div>';
    }
}
