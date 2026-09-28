<?php

use Redaxo\Core\RexVar\MediaListVar;
use Redaxo\Core\RexVar\MediaVar;
use Yakamara\YForm\Field\Media;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

return static function (Media $field, FieldContext $context): void {
    // The widget JS addresses its elements by id, so the id must be unique on the page. Derived from the context id (not hashed) so ids stay unique when inline rows are cloned from a template.
    $id = 'yform_' . str_replace('-', '_', $context->id);
    $name = escape($context->inputName());

    if ($field->multiple) {
        echo MediaListVar::getWidget($id, $name, implode(',', $field->fileNames($context->value)), $field->category, $field->types, $field->preview);
        return;
    }
    echo MediaVar::getWidget($id, $name, $field->fileNames($context->value)[0] ?? null, $field->category, $field->types, $field->preview);
};
