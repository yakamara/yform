<?php

use Redaxo\Core\Form\Widget\ArticleInput;
use Redaxo\Core\Form\Widget\ArticleListInput;
use Yakamara\YForm\Field\Article;
use Yakamara\YForm\Form\FieldContext;

return static function (Article $field, FieldContext $context): void {
    // Derived from the context id (not hashed) so ids stay unique when inline rows are cloned from a template.
    $id = 'yform_' . str_replace('-', '_', $context->id);
    $name = $context->inputName();
    $ids = $field->articleIds($context->value);

    if ($field->multiple) {
        echo new ArticleListInput($name, $id, $ids, $field->category)->render();
        return;
    }
    echo new ArticleInput($name, $id, $ids[0] ?? null, $field->category)->render();
};
