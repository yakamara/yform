<?php

use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Relation\HasMany;
use Yakamara\YForm\Field\Textarea;
use Yakamara\YForm\Form\FieldContext;
use Yakamara\YForm\Form\FieldGroup;
use Yakamara\YForm\View\ViewRenderer;

use function Redaxo\Core\View\escape;

/**
 * Inline editor of a has-many relation: one card per related record plus a `<template>` for new rows. With a sort
 * field the rows get buttons to move them; the submitted order is the stored order.
 *
 * @param list<array{id: ?int, values: array<string, mixed>}> $rows
 */
return static function (HasMany $field, FieldContext $context, array $rows): void {
    $theme = $context->theme;
    $fields = $field->inlineFields();
    $inlineCount = count(array_filter($fields, static fn ($f): bool => !$f instanceof Textarea && !$f instanceof HasMany));
    $wide = $inlineCount > 4;
    $sortable = null !== $field->sortField;
    $placeholder = $field->indexPlaceholder($context);

    $renderRow = static function (int|string $index, ?int $id, array $values) use ($field, $context, $fields, $theme, $wide, $inlineCount, $sortable): string {
        $cells = '';
        foreach ($fields as $name => $subField) {
            $width = $subField instanceof Textarea || $subField instanceof HasMany ? 12 : ($wide ? 6 : intdiv(12, max(1, $inlineCount)));
            $subContext = $field->rowContext($context, $subField, $index, HasMany::rowValue($values, $name, $subField));
            $cells .= '<div class="' . escape($theme->columnClass($width)) . '">' . ViewRenderer::render(FieldGroup::class, $subField, $subContext, true) . '</div>';
        }
        $prefix = $context->formName . '[' . $field->name . '][' . $index . ']';

        return '<div class="yform-inline-row" data-yform-inline-row>'
            . '<div class="yform-inline-row-head">'
            . '<span class="yform-inline-row-title">' . escape(null === $id ? I18n::msg('yform_inline_new') : '#' . $id) . '</span>'
            . '<input type="hidden" name="' . escape($prefix) . '[_id]" value="' . escape((string) $id) . '">'
            . '<input type="hidden" name="' . escape($prefix) . '[_delete]" value="" data-yform-inline-delete-flag>'
            . '<span class="yform-inline-row-actions">'
            . ($sortable
                ? '<button type="button" class="' . escape($theme->secondary) . ' btn-xs btn-sm" data-yform-inline-move="up" title="' . I18n::msg('yform_inline_up') . '" aria-label="' . I18n::msg('yform_inline_up') . '">↑</button>'
                . '<button type="button" class="' . escape($theme->secondary) . ' btn-xs btn-sm" data-yform-inline-move="down" title="' . I18n::msg('yform_inline_down') . '" aria-label="' . I18n::msg('yform_inline_down') . '">↓</button>'
                : '')
            . '<button type="button" class="' . escape($theme->secondary) . ' btn-xs btn-sm" data-yform-inline-remove>' . I18n::msg('yform_inline_remove') . '</button>'
            . '</span>'
            . '</div>'
            . '<div class="' . escape($theme->row) . '">' . $cells . '</div>'
            . '</div>';
    };

    $html = '';
    foreach ($rows as $index => $row) {
        $html .= $renderRow($index, $row['id'], $row['values']);
    }
    ?>
    <div class="yform-inline" data-yform-inline data-yform-inline-placeholder="<?= escape($placeholder) ?>" id="<?= escape($context->id) ?>">
        <input type="hidden" name="<?= escape($context->inputName('[' . $placeholder . ']')) ?>" value="">
        <div class="yform-inline-rows" data-yform-inline-rows><?= $html ?></div>
        <template data-yform-inline-template><?= $renderRow($placeholder, null, []) ?></template>
        <button type="button" class="<?= escape($theme->secondary) ?>" data-yform-inline-add>
            + <?= I18n::msg('yform_inline_add', $field->targetTable()->label()) ?>
        </button>
    </div>
    <?php
};
