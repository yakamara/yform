<?php

use Yakamara\YForm\Field\Hidden;
use Yakamara\YForm\Form\FieldContext;

return static function (Hidden $field, FieldContext $context): void {
    echo '<input ', $field->controlAttributes($context, ['type' => 'hidden', 'value' => (string) $context->value, 'required' => null], themeClass: false), '>';
};
