<?php
$css_classes = [];
if ($this->getElement('css_classes') != '') {
    $css_classes = explode(',', $this->getElement('css_classes'));
}

$labels = explode(',', $this->getElement('labels'));
foreach ($labels as $index => $label) {
    $classes = ['btn'];
    if (isset($css_classes[$index]) && trim($css_classes[$index]) != '') {
        $classes[] = trim($css_classes[$index]);
    }

    if ($this->getWarningClass() != '') {
        $classes[] = $this->getWarningClass();
    }

    $id = $this->getFieldId() . '-' . rex_string::normalize($label);
    $value = htmlspecialchars(stripslashes(rex_i18n::translate($label)));

    echo '<button class="' . implode(' ', $classes) . '" type="submit" name="' . $this->getFieldName() . '" id="'. $id .'" value="' . $value . '">' . $label . '</button>';
}


?>
