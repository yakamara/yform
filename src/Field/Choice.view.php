<?php

use Redaxo\Core\Core;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Field\Choice;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

return static function (Choice $field, FieldContext $context): void {
    $theme = $context->theme;
    $value = $context->value;

    if ($field->expanded) {
        $type = $field->multiple ? 'checkbox' : 'radio';
        $wrapper = $field->multiple ? $theme->checkWrapper : $theme->radioWrapper;
        $name = $context->inputName($field->multiple ? '[]' : '');
        $renderOptions = static function (array $options) use (&$renderOptions, $field, $context, $theme, $value, $type, $wrapper, $name): void {
            foreach ($options as $optionValue => $label) {
                if (is_array($label)) {
                    echo '<fieldset><legend>', escape(I18n::translate((string) $optionValue, false)), '</legend>';
                    $renderOptions($label);
                    echo '</fieldset>';
                    continue;
                }
                $id = $context->id . '-' . preg_replace('/[^a-z0-9_-]+/i', '-', (string) $optionValue);
                $input = new HtmlAttributes([
                    'type' => $type,
                    'name' => $name,
                    'id' => $id,
                    'value' => (string) $optionValue,
                    'checked' => $field->isSelected($optionValue, $value),
                    'disabled' => $field->readonly,
                    'class' => [$theme->checkInput => '' !== $theme->checkInput],
                ]);
                echo '<div class="', escape($wrapper), '">';
                if ('' !== $theme->checkLabel) {
                    echo '<input ', $input, '><label class="', escape($theme->checkLabel), '" for="', escape($id), '">', escape(I18n::translate($label, false)), '</label>';
                } else {
                    echo '<label for="', escape($id), '"><input ', $input, '> ', escape(I18n::translate($label, false)), '</label>';
                }
                echo '</div>';
            }
        };
        if ($field->multiple) {
            // Keeps the field present in the submission when nothing is checked.
            echo '<input type="hidden" name="', escape($context->inputName('[]')), '" value="">';
        }
        $renderOptions($field->getOptions());
        return;
    }

    // The select class replaces the generic control class (they differ in Bootstrap 5).
    $attributes = $field->controlAttributes($context, [
        'class' => [$theme->select => true, $theme->controlInvalid => '' !== $theme->controlInvalid && $context->hasErrors()],
        'name' => $context->inputName($field->multiple ? '[]' : ''),
        'multiple' => $field->multiple,
        'data-rex-select' => $field->multiple && Core::isBackend(),
        'readonly' => null,
        'disabled' => $field->readonly,
    ], themeClass: false);

    $renderOptions = static function (array $options) use (&$renderOptions, $field, $value): void {
        foreach ($options as $optionValue => $label) {
            if (is_array($label)) {
                echo '<optgroup label="', escape(I18n::translate((string) $optionValue, false)), '">';
                $renderOptions($label);
                echo '</optgroup>';
                continue;
            }
            echo '<option ', new HtmlAttributes(['value' => (string) $optionValue, 'selected' => $field->isSelected($optionValue, $value)]), '>',
            escape(I18n::translate($label, false)), '</option>';
        }
    };

    echo '<select ', $attributes, '>';
    if (!$field->multiple && (null !== $field->placeholder || !$field->required)) {
        echo '<option value="">', escape($field->placeholder ?? ''), '</option>';
    }
    $renderOptions($field->getOptions());
    echo '</select>';
};
