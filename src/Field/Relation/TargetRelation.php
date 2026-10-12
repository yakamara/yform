<?php

namespace Yakamara\YForm\Field\Relation;

use Closure;
use LogicException;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;

use function asort;
use function sprintf;

use const SORT_FLAG_CASE;
use const SORT_NATURAL;

/**
 * Common base of relations the user picks target records for ({@see BelongsTo}, {@see ManyToMany}).
 */
abstract class TargetRelation extends Field implements Relation
{
    /** @var class-string<Table>|string */
    public protected(set) string $target = '';
    public protected(set) ?string $placeholder = null;

    /** @var (Closure(Query): void)|null */
    public protected(set) ?Closure $filter = null;

    /** @param class-string<Table>|string $table target table class or name */
    public function target(string $table): static
    {
        $this->target = $table;
        return $this;
    }

    /** Restricts the selectable records: `->filter(fn (Query $q) => $q->where('status', 'active'))`. */
    public function filter(Closure $filter): static
    {
        $this->filter = $filter;
        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function targetTable(): Table
    {
        if ('' === $this->target) {
            throw new LogicException(sprintf('Relation field "%s" has no target table; call ->target().', $this->name));
        }
        return Tables::get($this->target);
    }

    /** Query over the selectable target records. */
    public function targetQuery(): Query
    {
        $query = $this->targetTable()->query();
        if (null !== $this->filter) {
            ($this->filter)($query);
        }
        return $query;
    }

    /** @return array<int, string> id → label of the selectable records */
    public function options(): array
    {
        $options = [];
        foreach ($this->targetQuery()->find() as $record) {
            $options[(int) $record->id] = $record->label();
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }

    /** @return array<int, string> id → label of all target records, including those {@see filter()} hides from selection */
    public function searchOptions(): array
    {
        $options = [];
        foreach ($this->targetTable()->query()->find() as $record) {
            $options[(int) $record->id] = $record->label();
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }
}
