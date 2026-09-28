<?php

use Redaxo\Core\Core;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Field\Relation\ManyToMany;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

return static function (ManyToMany $field, FieldContext $context): void {
    $theme = $context->theme;
    $selected = array_map('intval', (array) $context->value);
    $options = $field->options();

    // Keeps the field in the submission when nothing is selected.
    echo '<input type="hidden" name="', escape($context->inputName('[]')), '" value="">';

    if ($field->expanded) {
        foreach ($options as $id => $label) {
            $inputId = $context->id . '-' . $id;
            $input = new HtmlAttributes([
                'type' => 'checkbox',
                'name' => $context->inputName('[]'),
                'id' => $inputId,
                'value' => (string) $id,
                'checked' => in_array($id, $selected, true),
                'disabled' => $field->readonly,
                'class' => [$theme->checkInput => '' !== $theme->checkInput],
            ]);
            echo '<div class="', escape($theme->checkWrapper), '">';
            if ('' !== $theme->checkLabel) {
                echo '<input ', $input, '><label class="', escape($theme->checkLabel), '" for="', escape($inputId), '">', escape($label), '</label>';
            } else {
                echo '<label for="', escape($inputId), '"><input ', $input, '> ', escape($label), '</label>';
            }
            echo '</div>';
        }
        return;
    }

    $attributes = $field->controlAttributes($context, [
        'class' => [$theme->select => true, 'selectpicker' => Core::isBackend(), $theme->controlInvalid => '' !== $theme->controlInvalid && $context->hasErrors()],
        'name' => $context->inputName('[]'),
        'multiple' => true,
        'data-live-search' => Core::isBackend() && count($options) > 10 ? 'true' : null,
        'readonly' => null,
        'disabled' => $field->readonly,
    ], themeClass: false);

    echo '<select ', $attributes, '>';
    foreach ($options as $id => $label) {
        echo '<option ', new HtmlAttributes(['value' => (string) $id, 'selected' => in_array($id, $selected, true)]), '>', escape($label), '</option>';
    }
    echo '</select>';
};
