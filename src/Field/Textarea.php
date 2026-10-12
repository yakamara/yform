<?php

namespace Yakamara\YForm\Field;

use Override;
use Yakamara\YForm\Data\Record;

use function is_scalar;

/** Multi-line text, stored as `text` (or `mediumtext` with `->large()`). */
class Textarea extends Field
{
    public protected(set) int $rows = 6;
    public protected(set) bool $large = false;

    #[Override]
    protected function init(): void
    {
        $this->listed = false;
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    /** Up to 16 MB instead of 64 KB. */
    public function large(bool $large = true): static
    {
        $this->large = $large;
        return $this;
    }

    #[Override]
    protected function columnType(): string
    {
        return $this->large ? 'mediumtext' : 'text';
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        if (!is_scalar($input)) {
            return null;
        }
        // Keep inner whitespace and line breaks, only normalise line endings and outer blank space.
        $input = trim(str_replace("\r\n", "\n", (string) $input));
        return '' === $input ? null : $input;
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        return (string) $value;
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        $text = (string) $value;
        return \Redaxo\Core\View\escape(mb_strlen($text) > 80 ? mb_substr($text, 0, 80) . '…' : $text);
    }
}
