<?php

/**
 * @var \Yakamara\YForm\View\Fragment $this
 * @var \Yakamara\YForm\Value\AbstractValue $yfield
 */
extract($this->getVariables());
$option ??= '';

switch ($option) {
    case 'open':
        $attributes = [
            'class' => $yfield->getHTMLClass(),
            'id' => $yfield->getHTMLId(),
        ];

        $attributes = $yfield->getAttributeElements($attributes, []);
        echo '<fieldset ' . implode(' ', $attributes) . '>';
        if ($yfield->getLabel()) {
            echo '<legend id="' . $yfield->getFieldId() . '">' . $yfield->getLabel() . '</legend>';
        }
        break;
    case 'close':
        echo '</fieldset>';
        break;
}
