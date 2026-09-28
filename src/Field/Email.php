<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Validation\ValidationContext;

use function filter_var;

use const FILTER_VALIDATE_EMAIL;

/** E-mail address with format check. */
class Email extends Text
{
    #[Override]
    protected function init(): void
    {
        parent::init();
        $this->setInputType('email');
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        $value = parent::fromInput($input);
        return null === $value ? null : mb_strtolower($value);
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        if (!$this->isEmpty($value) && false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = I18n::rawMsg('yform_validation_email', $this->translatedLabel());
        }
        return $errors;
    }
}
