<?php

namespace Yakamara\YForm\Validation\Rule;

use DateTimeInterface;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\ValidationContext;

use function is_numeric;

/** Numeric (or date) value between `min` and `max` (both optional, inclusive). */
final readonly class Range implements Rule
{
    public function __construct(
        public int|float|string|DateTimeInterface|null $min = null,
        public int|float|string|DateTimeInterface|null $max = null,
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, Field $field, ValidationContext $context): ?string
    {
        if (!is_numeric($value) && !$value instanceof DateTimeInterface) {
            return null;
        }
        if (null !== $this->min && $value < $this->min) {
            return $this->message ?? I18n::rawMsg('yform_validation_min', $field->translatedLabel(), self::format($this->min));
        }
        if (null !== $this->max && $value > $this->max) {
            return $this->message ?? I18n::rawMsg('yform_validation_max', $field->translatedLabel(), self::format($this->max));
        }
        return null;
    }

    private static function format(int|float|string|DateTimeInterface $bound): string
    {
        return $bound instanceof DateTimeInterface ? $bound->format('d.m.Y') : (string) $bound;
    }
}
