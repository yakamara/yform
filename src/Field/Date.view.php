<?php

use Yakamara\YForm\Field\Date;
use Yakamara\YForm\Form\FieldContext;

return static function (Date $field, FieldContext $context): void {
    echo '<input ', $field->controlAttributes($context, [
        'type' => $field->inputType(),
        'value' => $field->inputValue($context->value),
        'min' => null === $field->min ? null : $field->inputValue($field->min),
        'max' => null === $field->max ? null : $field->inputValue($field->max),
    ]), '>';
};
