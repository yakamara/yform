<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Search\Filter;
use Yakamara\YForm\Search\RangeFilter;
use Yakamara\YForm\Validation\Rule\Range;
use Yakamara\YForm\Validation\ValidationContext;

use function filter_var;
use function is_int;

use const FILTER_VALIDATE_INT;

/** Whole number, stored as `int` (or `bigint`). */
class Integer extends Field
{
    public protected(set) ?int $min = null;
    public protected(set) ?int $max = null;
    public protected(set) bool $unsigned = false;
    public protected(set) bool $big = false;

    public function min(?int $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(?int $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function unsigned(bool $unsigned = true): static
    {
        $this->unsigned = $unsigned;
        return $this;
    }

    public function big(bool $big = true): static
    {
        $this->big = $big;
        return $this;
    }

    #[Override]
    protected function columnType(): string
    {
        return ($this->big ? 'bigint(20)' : 'int(11)') . ($this->unsigned ? ' unsigned' : '');
    }

    #[Override]
    protected function columnDefault(): ?string
    {
        return null === $this->default ? null : (string) (int) $this->default;
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        return null === $raw ? null : (int) $raw;
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        return [$this->name => $this->isEmpty($value) ? null : (int) $value];
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        $value = parent::fromInput($input);
        if (null === $value) {
            return null;
        }
        $int = filter_var($value, FILTER_VALIDATE_INT);
        // Keep invalid input as string so validation can report it and the form shows it again.
        return false === $int ? $value : $int;
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        if (!$this->isEmpty($value) && !is_int($value)) {
            return [I18n::rawMsg('yform_validation_integer', $this->translatedLabel())];
        }
        $errors = parent::validate($value, $context);
        if (!$this->isEmpty($value) && (null !== $this->min || null !== $this->max)) {
            $message = new Range($this->min, $this->max)->validate($value, $this, $context);
            if (null !== $message) {
                $errors[] = $message;
            }
        }
        return $errors;
    }

    #[Override]
    public function searchFilter(): ?Filter
    {
        return new RangeFilter($this, 'number', static function (string $input): ?int {
            $value = filter_var($input, FILTER_VALIDATE_INT);
            return false === $value ? null : $value;
        });
    }
}
