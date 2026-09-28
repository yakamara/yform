<?php

namespace Yakamara\YForm\Validation\Rule;

use Redaxo\Core\Security\PasswordPolicy as CorePasswordPolicy;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Validation\Rule;
use Yakamara\YForm\Validation\ValidationContext;

/** Checks the plain password against the core password policy (default rules or custom options). */
final readonly class PasswordPolicy implements Rule
{
    /** @param array<string, array{min?: int, max?: int}> $options */
    public function __construct(
        public array $options = [],
    ) {}

    public function validate(mixed $value, Field $field, ValidationContext $context): ?string
    {
        $policy = new CorePasswordPolicy($this->options ?: ['length' => ['min' => 8, 'max' => 4096]]);
        $result = $policy->check((string) $value);
        return true === $result ? null : $result;
    }
}
