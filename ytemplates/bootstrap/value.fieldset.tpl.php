<?php

use Yakamara\YForm\Value\AbstractValue;
use Yakamara\YForm\YForm;

/** @var AbstractValue|YForm $this */
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
