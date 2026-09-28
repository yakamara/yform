<?php

use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Form\FieldContext;

return static function (Text $field, FieldContext $context): void {
    $attributes = $field->controlAttributes($context, [
        'type' => $field->inputType,
        'value' => null === $context->value ? '' : (string) $context->value,
        'maxlength' => $field->maxLength,
    ]);
    echo '<input ', $attributes, '>';
};
