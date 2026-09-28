<?php

use Redaxo\Core\RexVar\LinkListVar;
use Redaxo\Core\RexVar\LinkVar;
use Yakamara\YForm\Field\Article;
use Yakamara\YForm\Form\FieldContext;

use function Redaxo\Core\View\escape;

return static function (Article $field, FieldContext $context): void {
    // Derived from the context id (not hashed) so ids stay unique when inline rows are cloned from a template.
    $id = 'yform_' . str_replace('-', '_', $context->id);
    $name = escape($context->inputName());
    $ids = $field->articleIds($context->value);

    if ($field->multiple) {
        echo LinkListVar::getWidget($id, $name, implode(',', $ids), $field->category);
        return;
    }
    echo LinkVar::getWidget($id, $name, $ids[0] ?? null, $field->category);
};
