<?php

use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Relation\HasMany;
use Yakamara\YForm\Field\Textarea;
use Yakamara\YForm\Form\FieldContext;
use Yakamara\YForm\Form\FieldGroup;
use Yakamara\YForm\View\ViewRenderer;

use function Redaxo\Core\View\escape;

/**
 * Inline editor of a has-many relation: one card per related record plus a `<template>` for new rows.
 *
 * @param list<array{id: ?int, values: array<string, mixed>}> $rows
 */
return static function (HasMany $field, FieldContext $context, array $rows): void {
    $theme = $context->theme;
    $fields = $field->inlineFields();
    $wide = count($fields) > 4;

    $renderRow = static function (int|string $index, ?int $id, array $values) use ($field, $context, $fields, $theme, $wide): string {
        $cells = '';
        foreach ($fields as $name => $subField) {
            $width = $subField instanceof Textarea ? 12 : ($wide ? 6 : intdiv(12, max(1, count($fields))));
            $subContext = $field->rowContext($context, $subField, $index, HasMany::rowValue($values, $name, $subField));
            $cells .= '<div class="' . escape($theme->columnClass($width)) . '">' . ViewRenderer::render(FieldGroup::class, $subField, $subContext, true) . '</div>';
        }
        $prefix = $context->formName . '[' . $field->name . '][' . $index . ']';

        return '<div class="yform-inline-row" data-yform-inline-row>'
            . '<div class="yform-inline-row-head">'
            . '<span class="yform-inline-row-title">' . escape(null === $id ? I18n::msg('yform_inline_new') : '#' . $id) . '</span>'
            . '<input type="hidden" name="' . escape($prefix) . '[_id]" value="' . escape((string) $id) . '">'
            . '<input type="hidden" name="' . escape($prefix) . '[_delete]" value="" data-yform-inline-delete-flag>'
            . '<button type="button" class="' . escape($theme->secondary) . ' btn-xs btn-sm" data-yform-inline-remove>' . escape(I18n::msg('yform_inline_remove')) . '</button>'
            . '</div>'
            . '<div class="' . escape($theme->row) . '">' . $cells . '</div>'
            . '</div>';
    };

    $html = '';
    foreach ($rows as $index => $row) {
        $html .= $renderRow($index, $row['id'], $row['values']);
    }
    ?>
    <div class="yform-inline" data-yform-inline id="<?= escape($context->id) ?>">
        <input type="hidden" name="<?= escape($context->inputName('[__index__]')) ?>" value="">
        <div class="yform-inline-rows" data-yform-inline-rows><?= $html ?></div>
        <template data-yform-inline-template><?= $renderRow('__index__', null, []) ?></template>
        <button type="button" class="<?= escape($theme->secondary) ?>" data-yform-inline-add>
            + <?= escape(I18n::msg('yform_inline_add', $field->targetTable()->label())) ?>
        </button>
    </div>
    <?php
};
