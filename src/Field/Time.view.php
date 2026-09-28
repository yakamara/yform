<?php

use Yakamara\YForm\Field\Time;
use Yakamara\YForm\Form\FieldContext;

return static function (Time $field, FieldContext $context): void {
    echo '<input ', $field->controlAttributes($context, ['type' => 'time', 'value' => (string) $context->value]), '>';
};
