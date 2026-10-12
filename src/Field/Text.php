<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Validation\ValidationContext;

use function mb_strlen;

/** Single-line text, stored as `varchar(maxLength)`. */
class Text extends Field
{
    public protected(set) int $maxLength = 191;
    public protected(set) string $inputType = 'text';

    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        return $this->attributes(['placeholder' => $placeholder]);
    }

    protected function setInputType(string $type): void
    {
        $this->inputType = $type;
    }

    #[Override]
    protected function columnType(): string
    {
        return 'varchar(' . $this->maxLength . ')';
    }

    #[Override]
    protected function columnDefault(): ?string
    {
        return null === $this->default ? null : (string) $this->default;
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        if (!$this->isEmpty($value) && mb_strlen((string) $value) > $this->maxLength) {
            $errors[] = I18n::rawMsg('yform_validation_max_length', $this->translatedLabel(), $this->maxLength);
        }
        return $errors;
    }
}
