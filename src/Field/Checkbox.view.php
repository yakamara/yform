<?php

use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Field\Checkbox;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

return static function (Checkbox $field, FieldContext $context): void {
    $theme = $context->theme;
    $input = $field->controlAttributes($context, [
        'type' => 'checkbox',
        'value' => '1',
        'checked' => (bool) $context->value,
        'class' => [$theme->checkInput => '' !== $theme->checkInput],
        'readonly' => null,
        'disabled' => $field->readonly,
    ], themeClass: false);

    $group = new HtmlAttributes(['class' => [$theme->checkWrapper, $theme->groupInvalid => $context->hasErrors() && '' !== $theme->groupInvalid]]);
    ?>
    <div <?= $group ?>>
        <?php // hidden fallback so an unchecked box is submitted as "0"?>
        <input type="hidden" name="<?= escape($context->inputName()) ?>" value="0">
        <?php if ('' !== $theme->checkLabel): ?>
            <input <?= $input ?>>
            <label class="<?= escape($theme->checkLabel) ?>" for="<?= escape($context->id) ?>"><?= escape($field->translatedLabel()) ?></label>
        <?php else: ?>
            <label for="<?= escape($context->id) ?>"><input <?= $input ?>> <?= escape($field->translatedLabel()) ?></label>
        <?php endif ?>
        <?php if (null !== $field->notice): ?>
            <p class="<?= escape($theme->help) ?>"><?= escape($field->notice) ?></p>
        <?php endif ?>
        <?php foreach ($context->errors as $error): ?>
            <p class="<?= escape($theme->error) ?>"><?= escape($error) ?></p>
        <?php endforeach ?>
    </div>
    <?php
};
