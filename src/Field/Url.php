<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Validation\ValidationContext;

use function filter_var;
use function preg_match;
use function Redaxo\Core\View\escape;

use const FILTER_VALIDATE_URL;

/** Absolute http(s) URL with format check. */
class Url extends Text
{
    #[Override]
    protected function init(): void
    {
        parent::init();
        $this->setInputType('url');
        $this->maxLength(2000);
    }

    #[Override]
    protected function columnType(): string
    {
        return $this->maxLength > 191 ? 'text' : parent::columnType();
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        if (!$this->isEmpty($value) && (false === filter_var($value, FILTER_VALIDATE_URL) || 1 !== preg_match('~^https?://~i', (string) $value))) {
            $errors[] = I18n::rawMsg('yform_validation_url', $this->translatedLabel());
        }
        return $errors;
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        if ($this->isEmpty($value)) {
            return '';
        }
        return '<a href="' . escape((string) $value) . '" target="_blank" rel="noopener">' . escape((string) $value) . '</a>';
    }
}
