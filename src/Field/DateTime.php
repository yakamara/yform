<?php

namespace Yakamara\YForm\Field;

use DateTimeImmutable;
use Override;

/** Date and time, stored as `datetime`, PHP value `DateTimeImmutable`. */
class DateTime extends Date
{
    protected string $storageFormat = 'Y-m-d H:i:s';
    protected string $inputFormat = 'Y-m-d\TH:i';
    protected string $displayFormat = 'd.m.Y H:i';
    protected string $inputPattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/';

    #[Override]
    protected function columnType(): string
    {
        return 'datetime';
    }

    #[Override]
    protected function parse(string $value): ?DateTimeImmutable
    {
        // Browsers may submit seconds ("…T10:15:00").
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}):\d{2}$/', $value, $m)) {
            $value = $m[1];
        }
        return parent::parse($value);
    }

    #[Override]
    public function inputType(): string
    {
        return 'datetime-local';
    }

    #[Override]
    protected function searchBound(DateTimeImmutable $day, bool $upper): string
    {
        return $day->format('Y-m-d') . ($upper ? ' 23:59:59' : ' 00:00:00');
    }
}
