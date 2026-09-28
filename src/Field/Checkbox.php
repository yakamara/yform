<?php

namespace Yakamara\YForm\Field;

use Override;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;

/** Boolean, stored as `tinyint(1)` (0/1, never NULL). */
class Checkbox extends Field
{
    #[Override]
    protected function init(): void
    {
        $this->default = false;
    }

    #[Override]
    public function columns(): array
    {
        return [new Column($this->name, 'tinyint(1)', false, $this->default ? '1' : '0')];
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        return (bool) $raw;
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        return [$this->name => $value ? 1 : 0];
    }

    #[Override]
    public function isEmpty(mixed $value): bool
    {
        return !$value;
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        return null !== $input && '' !== $input && '0' !== $input;
    }

    #[Override]
    public function wrapped(): bool
    {
        return false;
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return $value ? '<i class="rex-icon fa-check" title="' . \Redaxo\Core\View\escape(I18n::rawMsg('yform_yes')) . '"></i>' : '';
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        return $value ? I18n::rawMsg('yform_yes') : I18n::rawMsg('yform_no');
    }

    #[Override]
    public function applySearch(Query $query, string $term): void {}
}
