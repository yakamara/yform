<?php

namespace Yakamara\YForm\Form;

use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Form\Theme\Theme;

use function preg_replace;

/**
 * Everything a field needs to render its control inside a concrete form.
 */
final readonly class FieldContext
{
    public string $id;

    /** @param list<string> $errors */
    public function __construct(
        public string $formName,
        public Field $field,
        public mixed $value,
        public Theme $theme,
        public array $errors = [],
        public ?Record $record = null,
    ) {
        $this->id = (string) preg_replace('/[^a-z0-9_-]+/i', '-', $formName . '-' . $field->name);
    }

    /** Name attribute of the control, e.g. `contact[email]`, optionally with a suffix like `[]`. */
    public function inputName(string $suffix = ''): string
    {
        return $this->formName . '[' . $this->field->name . ']' . $suffix;
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }
}
