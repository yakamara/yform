<?php

namespace Yakamara\YForm\Field;

use DateTimeImmutable;
use DateTimeInterface;
use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Validation\Rule\Range;
use Yakamara\YForm\Validation\ValidationContext;

use function is_string;
use function preg_match;

use const STR_PAD_LEFT;

/**
 * Calendar date, stored as `date`, PHP value `DateTimeImmutable` (time 00:00).
 *
 * The native date picker of the browser is used; `min`/`max` accept dates or relative strings like `'today'`.
 */
class Date extends Field
{
    public protected(set) DateTimeInterface|string|null $min = null;
    public protected(set) DateTimeInterface|string|null $max = null;

    protected string $storageFormat = 'Y-m-d';
    protected string $inputFormat = 'Y-m-d';
    protected string $displayFormat = 'd.m.Y';
    protected string $inputPattern = '/^\d{4}-\d{2}-\d{2}$/';

    public function min(DateTimeInterface|string|null $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(DateTimeInterface|string|null $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function displayFormat(string $format): static
    {
        $this->displayFormat = $format;
        return $this;
    }

    #[Override]
    protected function columnType(): string
    {
        return 'date';
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        if (null === $raw || '' === $raw || str_starts_with((string) $raw, '0000-00-00')) {
            return null;
        }
        return new DateTimeImmutable((string) $raw);
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        return [$this->name => $value instanceof DateTimeInterface ? $value->format($this->storageFormat) : null];
    }

    #[Override]
    public function defaultValue(): mixed
    {
        return is_string($this->default) ? new DateTimeImmutable($this->default) : $this->default;
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        $value = parent::fromInput($input);
        if (null === $value) {
            return null;
        }
        return $this->parse($value) ?? $value;
    }

    /** Strict parsing: impossible dates like 2026-02-31 are rejected instead of silently normalised. */
    protected function parse(string $value): ?DateTimeImmutable
    {
        if (1 !== preg_match($this->inputPattern, $value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!' . $this->inputFormat, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (false === $date || (false !== $errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }
        return $date;
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        if (!$this->isEmpty($value) && !$value instanceof DateTimeInterface) {
            return [I18n::rawMsg('yform_validation_date', $this->translatedLabel())];
        }
        $errors = parent::validate($value, $context);
        if ($value instanceof DateTimeInterface && (null !== $this->min || null !== $this->max)) {
            $message = new Range($this->bound($this->min), $this->bound($this->max))->validate($value, $this, $context);
            if (null !== $message) {
                $errors[] = $message;
            }
        }
        return $errors;
    }

    protected function bound(DateTimeInterface|string|null $bound): ?DateTimeInterface
    {
        return is_string($bound) ? new DateTimeImmutable($bound) : $bound;
    }

    /** Value for the `value`/`min`/`max` attributes of the native input. */
    public function inputValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        $value = $value instanceof DateTimeInterface ? $value : $this->bound($value);
        return $value?->format($this->inputFormat) ?? '';
    }

    public function inputType(): string
    {
        return 'date';
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        return $value instanceof DateTimeInterface ? $value->format($this->displayFormat) : '';
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return \Redaxo\Core\View\escape($this->textValue($value));
    }

    #[Override]
    public function applySearch(Query $query, string $term): void
    {
        // "24.12.2026", "12.2026" or "2026"
        if (preg_match('/^(?:(\d{1,2})\.)?(?:(\d{1,2})\.)?(\d{4})$/', $term, $m)) {
            $parts = array_values(array_filter([$m[3], $m[2] ?? '', $m[1] ?? ''], static fn (string $p): bool => '' !== $p));
            $pattern = implode('-', array_map(static fn (string $p): string => str_pad($p, 2, '0', STR_PAD_LEFT), $parts));
            $query->whereRaw($query->column($this->name) . ' LIKE ?', [$pattern . '%']);
        }
    }
}
