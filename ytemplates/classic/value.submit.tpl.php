<?php

if (count($labels) == 1) {
    echo '<p class="formsubmit '.$this->getHTMLClass().'">';
} else {
    echo '<p class="formsubmit formsubmits '.$this->getHTMLClass().'">';
}

$css_classes = $this->getElement('css_classes');
if ($css_classes == '') {
    $css_classes = [];
} else {
    $css_classes = explode(',', $this->getElement('css_classes'));
}

foreach ($labels as $label_index => $label) {
    $classes = [];
    $classes[] = 'submit';

    if ($this->getWarningClass() != '') {
        $classes[] = $this->getWarningClass();
    }

    $value = rex_i18n::translate($label, true);

    if (count($labels) > 1) {
        $id = $this->getFieldId($label_index);
    } else {
        $id = $this->getFieldId();
    }

    $key = array_search($label, $labels);
    if ($key !== false && isset($css_classes[$key])) {
        $classes[] = $css_classes[$key];
    }

    echo '<input type="submit" class="'.implode(' ', $classes).'" name="'.$this->getFieldName().'" id="'.$id.'" value="'.$label.'" />';
}

echo '</p>';
