<?php

use Yakamara\YForm\Field\Integer;
use Yakamara\YForm\Form\FieldContext;

return static function (Integer $field, FieldContext $context): void {
    echo '<input ', $field->controlAttributes($context, [
        'type' => 'number',
        'step' => '1',
        'min' => $field->min ?? ($field->unsigned ? 0 : null),
        'max' => $field->max,
        'value' => null === $context->value ? '' : (string) $context->value,
    ]), '>';
};
