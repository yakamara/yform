<?php

/**
 * @var rex_yform_value_submit $this
 * @psalm-scope-this rex_yform_value_submit
 */

$labels ??= [];

$css_classes = [];
if ('' != $this->getElement('css_classes')) {
    $css_classes = explode(',', $this->getElement('css_classes'));
}

if (count($labels) > 1) {
    if (rex::isBackend()) {
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

    if ('' != $this->getWarningClass()) {
        $classes[] = $this->getWarningClass();
    }

    $id = $this->getFieldId() . '-' . rex_string::normalize($label);
    $label_translated = rex_i18n::translate($label, true);

    echo '<button class="' . implode(' ', $classes) . '" type="submit" name="' . $this->getFieldName() . '" id="' . $id . '" value="' . rex_escape($label) . '">' . $label_translated . '</button>';
}

if (count($labels) > 1) {
    echo '</div>';
    if (rex::isBackend()) {
        echo '</div>';
    }
}
