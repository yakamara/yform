<?php

namespace Yakamara\YForm\Form\Action;

use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Field\Upload;
use Yakamara\YForm\Form\Definition\FormDefinition;
use Yakamara\YForm\Form\Form;

use function preg_replace_callback;
use function Redaxo\Core\View\escape;

/**
 * State shared by the actions of one submission.
 */
final class Submission
{
    /** Record written by {@see SaveRecord}, available to later actions. */
    public ?Record $record = null;

    /** Set by {@see Redirect}; the redirect happens after all actions ran. */
    public ?string $redirectUrl = null;

    /** Replaces the success message of the form (trusted HTML), see {@see ShowMessage}. */
    public ?string $message = null;

    public function __construct(
        public readonly FormDefinition $definition,
        public readonly Form $form,
    ) {}

    public function value(string $field): mixed
    {
        return $this->form->value($field);
    }

    /**
     * Absolute paths of the files of an {@see Upload} field — for mail attachments:
     * `new SendMail(…, attachments: fn (Submission $s) => $s->files('cv'))`.
     *
     * Before {@see SaveRecord} ran, these are the waiting uploads; afterwards the stored files of the record.
     *
     * @return list<string>
     */
    public function files(string $field): array
    {
        $upload = $this->record?->table->getFields()[$field] ?? $this->form->fields[$field] ?? null;
        if (!$upload instanceof Upload) {
            return [];
        }
        $record = $this->record ?? $this->form->record;
        $value = null !== $this->record && $this->record->has($field) ? $this->record->get($field) : $this->form->value($field);
        $paths = [];
        foreach ($upload->entries($value) as $entry) {
            $path = $upload->path($record, $entry);
            if (null !== $path) {
                $paths[] = $path;
            }
        }
        return $paths;
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
    public function interpolate(string $template, bool $escape = false): string
    {
        $texts = $this->texts();
        return (string) preg_replace_callback('/\{([a-z][a-z0-9_]*)\}/i', function (array $match) use ($texts, $escape): string {
            if ('id' === $match[1]) {
                return (string) $this->record?->id;
            }
            $text = $texts[$match[1]] ?? $match[0];
            return $escape ? escape($text) : $text;
        }, $template);
    }

    /** Like {@see interpolate()}, but escapes the values for use in trusted HTML templates. */
    public function interpolateHtml(string $template): string
    {
        return $this->interpolate($template, true);
    }
}
