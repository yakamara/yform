<?php

namespace Yakamara\YForm\Validation;

use RuntimeException;

use function implode;

final class ValidationException extends RuntimeException
{
    public function __construct(
        public readonly Errors $errors,
    ) {
        parent::__construct('Validation failed: ' . implode(' ', $errors->all()));
    }
}
