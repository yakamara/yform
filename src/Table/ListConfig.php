<?php

namespace Yakamara\YForm\Table;

use Closure;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;

/**
 * Backend list configuration of a table, returned by {@see Table::list()}.
 *
 * ```php
 * public function list(ListConfig $list): ListConfig
 * {
 *     return $list
 *         ->columns('name', 'price', 'status')
 *         ->sortBy('name')
 *         ->column('margin', 'Marge', fn (Record $r) => …)   // computed column (trusted HTML)
 *         ->filter(fn (Query $q) => $q->where('archived', false));
 * }
 * ```
 */
final class ListConfig
{
    /** @var list<string>|null `null` = all fields marked as listed */
    public private(set) ?array $columns = null;

    public private(set) string $sortBy = 'id';
    public private(set) string $sortDirection = 'DESC';
    public private(set) int $perPage = 50;

    /** @var array<string, array{label: string, render: Closure(Record): string}> */
    public private(set) array $computed = [];

    /** @var array<string, Closure(mixed, Record): string> */
    public private(set) array $formatters = [];

    /** @var list<Closure(Query): void> */
    public private(set) array $filters = [];

    public function columns(string ...$columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function sortBy(string $column, string $direction = 'ASC'): self
    {
        $this->sortBy = $column;
        $this->sortDirection = 'DESC' === strtoupper($direction) ? 'DESC' : 'ASC';
        return $this;
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = max(1, $perPage);
        return $this;
    }

    /**
     * Computed column (not a field). The closure returns trusted HTML. Add its name to {@see columns()} to position
     * it; otherwise it is appended.
     *
     * @param Closure(Record): string $render
     */
    public function column(string $name, string $label, Closure $render): self
    {
        $this->computed[$name] = ['label' => $label, 'render' => $render];
        return $this;
    }

    /**
     * Replaces the cell rendering of a field column; the closure receives the value and returns trusted HTML.
     *
     * @param Closure(mixed, Record): string $formatter
     */
    public function format(string $field, Closure $formatter): self
    {
        $this->formatters[$field] = $formatter;
        return $this;
    }

    /**
     * Restricts the records shown in the list (and available for editing), e.g. per user.
     *
     * @param Closure(Query): void $filter
     */
    public function filter(Closure $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }
}
