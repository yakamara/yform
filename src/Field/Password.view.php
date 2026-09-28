<?php

use Yakamara\YForm\Field\Password;
use Yakamara\YForm\Form\FieldContext;

return static function (Password $field, FieldContext $context): void {
    // Never echo a stored (hashed) password back; required only applies when nothing is stored yet.
    $attributes = $field->controlAttributes($context, [
        'type' => 'password',
        'value' => '',
        'maxlength' => $field->maxLength,
        'required' => $field->required && null === $context->value,
    ]);
    echo '<input ', $attributes, '>';
};
