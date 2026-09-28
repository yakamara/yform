<?php

use Yakamara\YForm\Field\Decimal;
use Yakamara\YForm\Form\FieldContext;

return static function (Decimal $field, FieldContext $context): void {
    // A text input with decimal keyboard: `type=number` would reject the decimal comma.
    echo '<input ', $field->controlAttributes($context, [
        'type' => 'text',
        'inputmode' => 'decimal',
        'value' => null === $context->value ? '' : (string) $context->value,
    ]), '>';
};
