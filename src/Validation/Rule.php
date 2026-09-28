<?php

namespace Yakamara\YForm\Validation;

use Yakamara\YForm\Field\Field;

/**
 * A validation rule attached to a field via `->rule(...)`.
 *
 * Rules only run for non-empty values; emptiness is the business of `->required()`.
 */
interface Rule
{
    /** Returns an error message, or `null` if the value is valid. */
    public function validate(mixed $value, Field $field, ValidationContext $context): ?string;
}
