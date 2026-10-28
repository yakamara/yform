<?php

use Redaxo\Core\Form\Widget\MediaInput;
use Redaxo\Core\Form\Widget\MediaListInput;
use Yakamara\YForm\Field\Media;
use Yakamara\YForm\Form\FieldContext;

return static function (Media $field, FieldContext $context): void {
    // Derived from the context id (not hashed) so ids stay unique when inline rows are cloned from a template.
    $id = 'yform_' . str_replace('-', '_', $context->id);
    $name = $context->inputName();

    if ($field->multiple) {
        echo new MediaListInput($name, $id, $field->fileNames($context->value), $field->category, $field->types, $field->preview)->render();
        return;
    }
    echo new MediaInput($name, $id, $field->fileNames($context->value)[0] ?? null, $field->category, $field->types, $field->preview)->render();
};
