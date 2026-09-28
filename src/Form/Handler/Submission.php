<?php

namespace Yakamara\YForm\Form\Handler;

use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Form\Definition\FormDefinition;
use Yakamara\YForm\Form\Form;

use function preg_replace_callback;

/**
 * State shared by the handlers of one submission.
 */
final class Submission
{
    /** Record written by {@see SaveRecord}, available to later handlers. */
    public ?Record $record = null;

    /** Set by {@see Redirect}; the redirect happens after all handlers ran. */
    public ?string $redirectUrl = null;

    /** Replaces the success message of the form (trusted HTML). */
    public ?string $message = null;

    public function __construct(
        public readonly FormDefinition $definition,
        public readonly Form $form,
    ) {}

    public function value(string $field): mixed
    {
        return $this->form->value($field);
    }

    /** Plain text representation of a field value (labels of choices, formatted dates, …). */
    public function text(string $field): string
    {
        $fieldObject = $this->form->fields[$field] ?? null;
        return null === $fieldObject ? '' : $fieldObject->textValue($this->form->value($field));
    }

    /** @return array<string, string> plain text of all fields */
    public function texts(): array
    {
        $texts = [];
        foreach ($this->form->fields as $name => $field) {
            if ($field->storesValue()) {
                $texts[$name] = $field->textValue($this->form->value($name));
            }
        }
        return $texts;
    }

    /** Replaces `{field}` placeholders with the plain text values; `{id}` with the saved record id. */
    public function interpolate(string $template): string
    {
        $texts = $this->texts();
        return (string) preg_replace_callback('/\{([a-z][a-z0-9_]*)\}/i', function (array $match) use ($texts): string {
            if ('id' === $match[1]) {
                return (string) $this->record?->id;
            }
            return $texts[$match[1]] ?? $match[0];
        }, $template);
    }
}
