<?php

namespace Yakamara\YForm\Validation\Rule;

use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\ValidationContext;

use function mb_strlen;

/** String length between `min` and `max` characters (both optional). */
final readonly class Length implements Rule
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, Field $field, ValidationContext $context): ?string
    {
        $length = mb_strlen((string) $value);
        if (null !== $this->min && $length < $this->min) {
            return $this->message ?? I18n::rawMsg('yform_validation_min_length', $field->translatedLabel(), $this->min);
        }
        if (null !== $this->max && $length > $this->max) {
            return $this->message ?? I18n::rawMsg('yform_validation_max_length', $field->translatedLabel(), $this->max);
        }
        return null;
    }
}
