<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Search\Filter;
use Yakamara\YForm\Search\RangeFilter;
use Yakamara\YForm\Validation\ValidationContext;

use function preg_match;
use function substr;

/** Time of day, stored as `time`, PHP value string `HH:MM`. */
class Time extends Field
{
    #[Override]
    protected function columnType(): string
    {
        return 'time';
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        return null === $raw ? null : substr((string) $raw, 0, 5);
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        return [$this->name => $this->isEmpty($value) ? null : $value . ':00'];
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        $value = parent::fromInput($input);
        return null === $value ? null : substr($value, 0, 5);
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        if (!$this->isEmpty($value) && 1 !== preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value)) {
            return [I18n::rawMsg('yform_validation_time', $this->translatedLabel())];
        }
        return parent::validate($value, $context);
    }

    #[Override]
    public function searchFilter(): ?Filter
    {
        return new RangeFilter($this, 'time', static function (string $input, bool $upper): ?string {
            $time = substr($input, 0, 5);
            return 1 === preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time . ($upper ? ':59' : ':00') : null;
        });
    }
}
