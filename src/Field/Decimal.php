<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Validation\Rule\Range;
use Yakamara\YForm\Validation\ValidationContext;

use function is_numeric;
use function number_format;
use function preg_match;
use function str_replace;

/**
 * Exact decimal number, stored as `decimal(precision, scale)`.
 *
 * The PHP value is a numeric string (e.g. `"19.90"`) to avoid float rounding. Input accepts a decimal comma.
 */
class Decimal extends Field
{
    public protected(set) int $precision = 10;
    public protected(set) int $scale = 2;
    public protected(set) int|float|string|null $min = null;
    public protected(set) int|float|string|null $max = null;

    public function precision(int $precision, int $scale = 2): static
    {
        $this->precision = $precision;
        $this->scale = $scale;
        return $this;
    }

    public function min(int|float|string|null $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(int|float|string|null $max): static
    {
        $this->max = $max;
        return $this;
    }

    #[Override]
    protected function columnType(): string
    {
        return 'decimal(' . $this->precision . ',' . $this->scale . ')';
    }

    #[Override]
    protected function columnDefault(): ?string
    {
        return null === $this->default ? null : number_format((float) $this->default, $this->scale, '.', '');
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        $value = parent::fromInput($input);
        if (null === $value) {
            return null;
        }
        $value = str_replace([' ', "\u{a0}"], '', $value);
        // Dots are thousands separators only together with a decimal comma ("1.234,56"). A lone dot is the decimal
        // point, as in values rendered back from the database ("0.125").
        if (preg_match('/^-?\d{1,3}(\.\d{3})+,\d+$/', $value)) {
            $value = str_replace('.', '', $value);
        }
        return str_replace(',', '.', $value);
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        if (!$this->isEmpty($value) && !is_numeric($value)) {
            return [I18n::rawMsg('yform_validation_decimal', $this->translatedLabel())];
        }
        $errors = parent::validate($value, $context);
        if (!$this->isEmpty($value)) {
            $integerDigits = $this->precision - $this->scale;
            $pattern = '/^-?(\d{1,' . $integerDigits . '}(\.\d{1,' . $this->scale . '})?|\.\d{1,' . $this->scale . '})$/';
            if (1 !== preg_match($pattern, (string) $value)) {
                $errors[] = I18n::rawMsg('yform_validation_decimal_format', $this->translatedLabel(), $integerDigits, $this->scale);
            }
            if (null !== $this->min || null !== $this->max) {
                $message = new Range($this->min, $this->max)->validate((float) $value, $this, $context);
                if (null !== $message) {
                    $errors[] = $message;
                }
            }
        }
        return $errors;
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return $this->isEmpty($value) ? '' : number_format((float) $value, $this->scale, ',', '.');
    }
}
