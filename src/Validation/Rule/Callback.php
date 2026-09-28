<?php

namespace Yakamara\YForm\Validation\Rule;

use Closure;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\ValidationContext;

/**
 * Inline rule: `new Callback(fn ($value) => str_ends_with($value, '.de') ? null : 'Nur .de-Adressen')`.
 *
 * The closure returns an error message or `null`.
 */
final readonly class Callback implements Rule
{
    /** @param Closure(mixed, Field, ValidationContext): ?string $callback */
    public function __construct(
        public Closure $callback,
    ) {}

    public function validate(mixed $value, Field $field, ValidationContext $context): ?string
    {
        return ($this->callback)($value, $field, $context);
    }
}
