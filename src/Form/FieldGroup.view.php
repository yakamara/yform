<?php

use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

return static function (Field $field, FieldContext $context, bool $editable): void {
    $theme = $context->theme;

    if (!$editable && $field->storesValue()) {
        ?>
        <div class="<?= escape($theme->group) ?>">
            <label class="<?= escape($theme->label) ?>"><?= escape($field->translatedLabel()) ?></label>
            <p class="<?= escape($theme->staticControl) ?>"><?= escape($field->textValue($context->value)) ?></p>
        </div>
        <?php
        return;
    }

    if (!$field->wrapped()) {
        echo $field->render($context);
        return;
    }

    $group = new HtmlAttributes(['class' => [
        $theme->group,
        $theme->groupInvalid => '' !== $theme->groupInvalid && $context->hasErrors(),
        'yform-field-' . $field->name,
        'yform-required' => $field->required,
    ]]);
    ?>
    <div <?= $group ?>>
        <label class="<?= escape($theme->label) ?>" for="<?= escape($context->id) ?>">
            <?= escape($field->translatedLabel()) ?><?php if ($field->required): ?> <span class="yform-required-mark" aria-hidden="true">*</span><?php endif ?>
        </label>
        <?= $field->render($context) ?>
        <?php if (null !== $field->notice): ?>
            <p class="<?= escape($theme->help) ?>"><?= escape($field->notice) ?></p>
        <?php endif ?>
        <?php foreach ($context->errors as $error): ?>
            <p class="<?= escape($theme->error) ?>"><?= escape($error) ?></p>
        <?php endforeach ?>
    </div>
    <?php
};
