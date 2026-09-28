<?php

namespace Yakamara\YForm\Field\Relation;

use Override;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Index;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Data\Collection;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Schema\TableSchema;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Validation\ValidationContext;

use function array_diff;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function implode;
use function is_array;
use function Redaxo\Core\View\escape;

/**
 * Many-to-many relation over a pivot table (`source_id`, `target_id`), created by the schema sync.
 * The value is the list of target ids.
 *
 * ```php
 * yield ManyToMany::new('tags')->label('Schlagworte')->target(TagTable::class);   // pivot rex_product_tags
 * $record->related('tags');   // → Collection
 * ```
 */
class ManyToMany extends TargetRelation
{
    public protected(set) ?string $pivot = null;
    public protected(set) bool $expanded = false;

    #[Override]
    protected function init(): void
    {
        $this->sortable = false;
        $this->default = [];
    }

    /** Name of the pivot table; default `<table>_<field>`. */
    public function pivot(string $table): static
    {
        $this->pivot = $table;
        return $this;
    }

    /** Checkboxes instead of a multi select. */
    public function expanded(bool $expanded = true): static
    {
        $this->expanded = $expanded;
        return $this;
    }

    public function pivotName(Table $table): string
    {
        return $this->pivot ?? $table->name . '_' . $this->name;
    }

    #[Override]
    public function columns(): array
    {
        return [];
    }

    #[Override]
    public function additionalTables(Table $table): array
    {
        return [new TableSchema(
            $this->pivotName($table),
            [Column::int('source_id', unsigned: true), Column::int('target_id', unsigned: true)],
            ['source_id', 'target_id'],
            [new Index('target_id', ['target_id'])],
        )];
    }

    #[Override]
    public function defaultValue(): mixed
    {
        return [];
    }

    #[Override]
    public function loadValue(Record $record): mixed
    {
        if (null === $record->id) {
            return [];
        }
        $sql = Sql::factory();
        $rows = $sql->getArray('SELECT target_id FROM ' . $sql->escapeIdentifier($this->pivotName($record->table)) . ' WHERE source_id = ? ORDER BY target_id', [$record->id]);
        return array_map(static fn (array $row): int => (int) $row['target_id'], $rows);
    }

    #[Override]
    public function toDb(mixed $value): array
    {
        return [];
    }

    #[Override]
    public function fromInput(mixed $input): mixed
    {
        $values = is_array($input) ? $input : [];
        return array_values(array_unique(array_filter(array_map('intval', $values))));
    }

    #[Override]
    public function isEmpty(mixed $value): bool
    {
        return [] === $value || null === $value;
    }

    #[Override]
    public function validate(mixed $value, ValidationContext $context): array
    {
        if ($this->isEmpty($value)) {
            return $this->required ? [I18n::rawMsg('yform_validation_required', $this->translatedLabel())] : [];
        }
        $ids = (array) $value;
        $found = $this->targetQuery()->whereIn('id', $ids)->ids();
        return [] !== array_diff($ids, $found) ? [I18n::rawMsg('yform_validation_relation', $this->translatedLabel())] : [];
    }

    #[Override]
    public function afterSave(Record $record, bool $isNew): void
    {
        if (!$record->isLoaded($this->name)) {
            return;
        }
        $pivot = $this->pivotName($record->table);
        $sql = Sql::factory();
        $sql->setQuery('DELETE FROM ' . $sql->escapeIdentifier($pivot) . ' WHERE source_id = ?', [$record->id]);
        foreach ((array) $record->get($this->name) as $targetId) {
            Sql::factory()->setTable($pivot)->setValue('source_id', $record->id)->setValue('target_id', (int) $targetId)->insert();
        }
    }

    #[Override]
    public function beforeDelete(Record $record): void
    {
        $sql = Sql::factory();
        $sql->setQuery('DELETE FROM ' . $sql->escapeIdentifier($this->pivotName($record->table)) . ' WHERE source_id = ?', [$record->id]);
    }

    /** @return Collection<Record> */
    public function related(Record $record): Collection
    {
        $ids = (array) $record->get($this->name);
        return [] === $ids ? new Collection() : $this->targetTable()->query()->whereIn('id', $ids)->find();
    }

    #[Override]
    public function textValue(mixed $value): string
    {
        $ids = array_map('intval', (array) $value);
        return [] === $ids ? '' : implode(', ', Labels::get($this->targetTable(), $ids));
    }

    #[Override]
    public function listValue(mixed $value, Record $record): string
    {
        return escape($this->textValue($value));
    }

    #[Override]
    public function applySearch(Query $query, string $term): void {}
}
