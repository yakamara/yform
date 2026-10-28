<?php

use Redaxo\Core\Core;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Field\Relation\BelongsTo;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

return static function (BelongsTo $field, FieldContext $context): void {
    $theme = $context->theme;
    $options = $field->options();
    $attributes = $field->controlAttributes($context, [
        'class' => [$theme->select => true, $theme->controlInvalid => '' !== $theme->controlInvalid && $context->hasErrors()],
        'data-rex-select' => Core::isBackend() && count($options) > 10,
        'data-rex-select-search' => Core::isBackend() && count($options) > 10,
        'readonly' => null,
        'disabled' => $field->readonly,
    ], themeClass: false);

    echo '<select ', $attributes, '>';
    echo '<option value="">', escape($field->placeholder ?? ''), '</option>';
    foreach ($options as $id => $label) {
        echo '<option ', new HtmlAttributes(['value' => (string) $id, 'selected' => $id === $context->value]), '>', escape($label), '</option>';
    }
    echo '</select>';
};
