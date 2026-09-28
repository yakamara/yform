<?php

namespace Yakamara\YForm\Validation\Rule;

use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\ValidationContext;

use function preg_match;

/** The (string) value must match the pattern, e.g. `new Regex('/^[A-Z]{2}\d+$/')`. */
final readonly class Regex implements Rule
{
    public function __construct(
        public string $pattern,
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, Field $field, ValidationContext $context): ?string
    {
        if (1 === preg_match($this->pattern, (string) $value)) {
            return null;
        }
        return $this->message ?? I18n::rawMsg('yform_validation_format', $field->translatedLabel());
    }
}
