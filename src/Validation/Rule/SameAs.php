<?php

namespace Yakamara\YForm\Validation\Rule;

use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\ValidationContext;

/** The value must equal the value of another field (e.g. password confirmation). */
final readonly class SameAs implements Rule
{
    public function __construct(
        public string $field,
        public ?string $message = null,
    ) {}

    public function validate(mixed $value, Field $field, ValidationContext $context): ?string
    {
        if ($value == $context->value($this->field)) {
            return null;
        }
        return $this->message ?? I18n::rawMsg('yform_validation_same_as', $field->translatedLabel());
    }
}
