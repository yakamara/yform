<?php

namespace Yakamara\YForm\Field\Relation;

use Override;
use Redaxo\Core\Database\Index;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Search\ChoiceFilter;
use Yakamara\YForm\Search\Filter;
use Yakamara\YForm\Validation\ValidationContext;

use function filter_var;
use function Redaxo\Core\View\escape;

use const FILTER_VALIDATE_INT;

/**
 * Reference to one record of another table, stored as id column with index (`category_id`).
 *
 * ```php
 * yield BelongsTo::new('category_id')->label('Kategorie')->target(CategoryTable::class)->onDelete(OnDelete::SetNull);
 * $record->related('category_id');   // → ?Record
 * ```
 */
class BelongsTo extends TargetRelation
{
    public protected(set) OnDelete $onDelete = OnDelete::Restrict;

    public function onDelete(OnDelete $onDelete): static
    {
        $this->onDelete = $onDelete;
        return $this;
    }

    #[Override]
    protected function columnType(): string
    {
        return 'int(10) unsigned';
    }

    #[Override]
    public function indexes(): array
    {
        return [...parent::indexes(), new Index($this->name, [$this->name])];
    }

    #[Override]
    public function fromDb(mixed $raw): mixed
    {
        return null === $raw ? null : (int) $raw;
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        return [$this->name => $this->isEmpty($value) ? null : (int) $value];
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        $value = parent::fromInput($input);
        if (null === $value) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return false === $id ? $value : $id;
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        $errors = parent::validate($value, $context);
        if (!$this->isEmpty($value) && !$this->targetQuery()->where('id', (int) $value)->exists()) {
            $errors[] = I18n::rawMsg('yform_validation_relation', $this->translatedLabel());
        }
        return $errors;
    }

    public function related(Record $record): ?Record
    {
        $id = $record->get($this->name);
        return null === $id ? null : $this->targetTable()->query()->where('id', $id)->first();
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        if (null === $value) {
            return '';
        }
        return Labels::get($this->targetTable(), [(int) $value])[(int) $value] ?? '#' . $value;
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return escape($this->textValue($value));
    }

    #[Override]
    public function searchFilter(): ?Filter
    {
        return new ChoiceFilter($this, $this->searchOptions());
    }
}
