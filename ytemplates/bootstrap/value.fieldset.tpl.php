<?php

/**
 * @var rex_yform_value_abstract|\Yakamara\YForm\YForm $this
 * @psalm-scope-this rex_yform_value_abstract
 */
$option ??= '';

switch ($option) {
    case 'open':
        $attributes = [
            'class' => $this->getHTMLClass(),
            'id' => $this->getHTMLId(),
        ];

        $attributes = $this->getAttributeElements($attributes, []);
        echo '<fieldset ' . implode(' ', $attributes) . '>';
        if ($this->getLabel()) {
            echo '<legend id="' . $this->getFieldId() . '">' . $this->getLabel() . '</legend>';
        }
        break;
    case 'close':
        echo '</fieldset>';
        break;
}
