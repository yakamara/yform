<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Core;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Validation\ValidationContext;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function pathinfo;
use function Redaxo\Core\View\escape;
use function strtolower;

use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

/**
 * File from the media pool (backend widget). Single file as `varchar`, `->multiple()` as JSON array in `text`.
 *
 * Deleting a media file that is referenced here is blocked (see `MEDIA_IS_IN_USE` in the addon).
 */
class Media extends Field
{
    public protected(set) bool $multiple = false;
    public protected(set) ?int $category = null;
    public protected(set) bool $preview = true;

    /** @var list<string> */
    public protected(set) array $types = [];

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /** Allowed file extensions, e.g. `->types('jpg', 'png', 'webp')`. */
    public function types(string ...$types): static
    {
        $this->types = array_values(array_map(strtolower(...), $types));
        return $this;
    }

    /** Media pool category opened by default. */
    public function category(?int $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function preview(bool $preview = true): static
    {
        $this->preview = $preview;
        return $this;
    }

    #[Override]
    protected function columnType(): string
    {
        return $this->multiple ? 'text' : 'varchar(191)';
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        if (!$this->multiple) {
            return null === $raw || '' === $raw ? null : (string) $raw;
        }
        $values = null === $raw || '' === $raw ? [] : json_decode((string) $raw, true);
        return is_array($values) ? array_values(array_map('strval', $values)) : [];
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        if (!$this->multiple) {
            return parent::toDb($value);
        }
        $value = is_array($value) ? array_values($value) : [];
        return [$this->name => [] === $value ? null : json_encode($value, JSON_UNESCAPED_UNICODE)];
    }

    #[Override]
    public function defaultValue(): mixed
    {
        return $this->multiple ? [] : $this->default;
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        if (!$this->multiple) {
            return parent::fromInput($input);
        }
        // The core media list widget submits a comma separated list.
        $input = is_array($input) ? implode(',', $input) : (string) $input;
        return array_values(array_filter(array_map('trim', explode(',', $input)), static fn (string $v): bool => '' !== $v));
    }

    /** @return list<string> */
    public function fileNames(mixed $value): array
    {
        if ($this->multiple) {
            return is_array($value) ? $value : [];
        }
        return null === $value || '' === $value ? [] : [(string) $value];
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        foreach ($this->fileNames($value) as $fileName) {
            if (null === \Redaxo\Core\MediaPool\Media::get($fileName)) {
                $errors[] = I18n::rawMsg('yform_validation_media_missing', $this->translatedLabel(), $fileName);
            } elseif ([] !== $this->types && !in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $this->types, true)) {
                $errors[] = I18n::rawMsg('yform_validation_media_type', $this->translatedLabel(), implode(', ', $this->types));
            }
        }
        return $errors;
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        return implode(', ', $this->fileNames($value));
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return escape($this->textValue($value));
    }

    /** Only the backend has the media pool; in frontend forms the field is not editable. */
    #[Override]
    public function isEditable(): bool
    {
        return parent::isEditable() && Core::isBackend();
    }
}
