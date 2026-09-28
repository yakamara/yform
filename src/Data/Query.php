<?php

namespace Yakamara\YForm\Data;

use BackedEnum;
use Closure;
use Countable;
use DateTimeInterface;
use InvalidArgumentException;
use IteratorAggregate;
use Redaxo\Core\Database\Sql;
use Traversable;
use Yakamara\YForm\Table\Table;

use function array_fill;
use function array_map;
use function count;
use function implode;
use function in_array;
use function is_array;
use function json_encode;
use function max;
use function sprintf;
use function strtoupper;

/**
 * Fluent query over one managed table, returning records.
 *
 * Column names are checked against the table definition, values are always bound as parameters.
 *
 * ```php
 * ProductTable::query()
 *     ->where('status', 'active')
 *     ->where('price', '>=', 10)
 *     ->whereAny(fn (Query $q) => $q->whereLike('name', '%tee%')->whereLike('description', '%tee%'))
 *     ->orderBy('name')
 *     ->limit(20)
 *     ->find();
 * ```
 *
 * @implements IteratorAggregate<int, Record>
 */
final class Query implements IteratorAggregate, Countable
{
    private const array OPERATORS = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];

    /** @var list<array{string, list<mixed>}> */
    private array $conditions = [];

    /** @var list<string> */
    private array $orders = [];

    private ?int $limit = null;
    private int $offset = 0;
    private string $boolean = 'AND';

    private readonly Sql $sql;

    public function __construct(
        public readonly Table $table,
    ) {
        $this->sql = Sql::factory();
    }

    // ---------------------------------------------------------------- conditions

    /** `where('status', 'active')` or `where('price', '>', 10)`. A `null` value compares with `IS NULL`. */
    public function where(string $column, mixed $operatorOrValue, mixed ...$value): self
    {
        if ([] === $value) {
            $operator = '=';
            $compare = $operatorOrValue;
        } else {
            $operator = strtoupper((string) $operatorOrValue);
            $compare = $value[0];
            if (!in_array($operator, self::OPERATORS, true)) {
                throw new InvalidArgumentException(sprintf('Unsupported operator "%s".', $operatorOrValue));
            }
        }

        if (null === $compare) {
            return match ($operator) {
                '=' => $this->whereNull($column),
                '!=', '<>' => $this->whereNotNull($column),
                default => throw new InvalidArgumentException('NULL can only be compared with = or !='),
            };
        }

        return $this->addCondition($this->column($column) . ' ' . $operator . ' ?', [$this->normalize($compare)]);
    }

    /** @param list<mixed> $values */
    public function whereIn(string $column, array $values): self
    {
        if ([] === $values) {
            return $this->addCondition('1 = 0', []);
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        return $this->addCondition($this->column($column) . ' IN (' . $placeholders . ')', array_map($this->normalize(...), $values));
    }

    /** @param list<mixed> $values */
    public function whereNotIn(string $column, array $values): self
    {
        if ([] === $values) {
            return $this;
        }
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        return $this->addCondition($this->column($column) . ' NOT IN (' . $placeholders . ')', array_map($this->normalize(...), $values));
    }

    public function whereNull(string $column): self
    {
        return $this->addCondition($this->column($column) . ' IS NULL', []);
    }

    public function whereNotNull(string $column): self
    {
        return $this->addCondition($this->column($column) . ' IS NOT NULL', []);
    }

    public function whereBetween(string $column, mixed $from, mixed $to): self
    {
        return $this->addCondition($this->column($column) . ' BETWEEN ? AND ?', [$this->normalize($from), $this->normalize($to)]);
    }

    /** Raw LIKE pattern, wildcards are not escaped: `whereLike('name', 'Tee%')`. */
    public function whereLike(string $column, string $pattern): self
    {
        return $this->addCondition($this->column($column) . ' LIKE ?', [$pattern]);
    }

    /** For JSON list columns (`Choice::multiple()`, `Media::multiple()`, …). */
    public function whereJsonContains(string $column, string|int $value): self
    {
        return $this->addCondition('JSON_CONTAINS(' . $this->column($column) . ', ?)', [json_encode($value)]);
    }

    /**
     * Raw SQL condition with positional `?` parameters. Use {@see column()} for column references.
     *
     * @param list<mixed> $params
     */
    public function whereRaw(string $sql, array $params = []): self
    {
        return $this->addCondition($sql, array_map($this->normalize(...), $params));
    }

    /** Group of conditions of which at least one must match: `whereAny(fn (Query $q) => $q->where(…)->where(…))`. */
    public function whereAny(Closure $callback): self
    {
        return $this->group($callback, 'OR');
    }

    /** Group of conditions which all must match (useful inside {@see whereAny()}). */
    public function whereAll(Closure $callback): self
    {
        return $this->group($callback, 'AND');
    }

    private function group(Closure $callback, string $boolean): self
    {
        $group = new self($this->table);
        $group->boolean = $boolean;
        $callback($group);
        [$sql, $params] = $group->compileWhere();
        return '' === $sql ? $this : $this->addCondition('(' . $sql . ')', $params);
    }

    /** @param list<mixed> $params */
    private function addCondition(string $sql, array $params): self
    {
        $this->conditions[] = [$sql, $params];
        return $this;
    }

    // ---------------------------------------------------------------- order and limit

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException(sprintf('Invalid sort direction "%s".', $direction));
        }
        $this->orders[] = $this->column($column) . ' ' . $direction;
        return $this;
    }

    /** Raw ORDER BY expression, e.g. `FIELD(status, "new", "done")`. */
    public function orderByRaw(string $expression): self
    {
        $this->orders[] = $expression;
        return $this;
    }

    public function resetOrder(): self
    {
        $this->orders = [];
        return $this;
    }

    public function limit(?int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = max(0, $offset);
        return $this;
    }

    // ---------------------------------------------------------------- execution

    /** @return Collection<Record> */
    public function find(): Collection
    {
        [$sql, $params] = $this->toSql();
        $records = [];
        foreach ($this->sql->getArray($sql, $params) as $row) {
            $records[] = $this->table->hydrate($row);
        }
        return new Collection($records);
    }

    public function first(): ?Record
    {
        $query = clone $this;
        $query->limit = 1;
        return $query->find()->first();
    }

    public function count(): int
    {
        [$where, $params] = $this->compileWhere();
        $sql = 'SELECT COUNT(*) AS c FROM ' . $this->sql->escapeIdentifier($this->table->name) . ('' === $where ? '' : ' WHERE ' . $where);
        return (int) ($this->sql->getArray($sql, $params)[0]['c'] ?? 0);
    }

    public function exists(): bool
    {
        [$where, $params] = $this->compileWhere();
        $sql = 'SELECT 1 FROM ' . $this->sql->escapeIdentifier($this->table->name) . ('' === $where ? '' : ' WHERE ' . $where) . ' LIMIT 1';
        return [] !== $this->sql->getArray($sql, $params);
    }

    /** @return list<int> */
    public function ids(): array
    {
        [$where, $params] = $this->compileWhere();
        $sql = 'SELECT ' . $this->column('id') . ' FROM ' . $this->sql->escapeIdentifier($this->table->name)
            . ('' === $where ? '' : ' WHERE ' . $where) . $this->compileTail();
        return array_map(static fn (array $row): int => (int) $row['id'], $this->sql->getArray($sql, $params));
    }

    /** @return Paginated<Record> */
    public function paginate(int $page, int $perPage): Paginated
    {
        $total = $this->count();
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $pages);

        $query = clone $this;
        $query->limit($perPage, ($page - 1) * $perPage);

        return new Paginated($query->find(), $total, $page, $perPage);
    }

    /** Deletes all matching records one by one (hooks and relations included). */
    public function delete(): int
    {
        $count = 0;
        foreach ($this->find() as $record) {
            $record->delete();
            ++$count;
        }
        return $count;
    }

    /** @return Traversable<int, Record> */
    public function getIterator(): Traversable
    {
        return $this->find()->getIterator();
    }

    /** @return array{string, list<mixed>} */
    public function toSql(): array
    {
        [$where, $params] = $this->compileWhere();
        $sql = 'SELECT * FROM ' . $this->sql->escapeIdentifier($this->table->name)
            . ('' === $where ? '' : ' WHERE ' . $where)
            . $this->compileTail();
        return [$sql, $params];
    }

    // ---------------------------------------------------------------- helpers

    /** Escaped, table-qualified column reference; throws for columns the table does not define. */
    public function column(string $column): string
    {
        if (!$this->table->hasColumn($column)) {
            throw new InvalidArgumentException(sprintf('Table "%s" has no column "%s".', $this->table->name, $column));
        }
        return $this->sql->escapeIdentifier($this->table->name) . '.' . $this->sql->escapeIdentifier($column);
    }

    /** @return array{string, list<mixed>} */
    private function compileWhere(): array
    {
        $parts = [];
        $params = [];
        foreach ($this->conditions as [$sql, $conditionParams]) {
            $parts[] = $sql;
            foreach ($conditionParams as $param) {
                $params[] = $param;
            }
        }
        return [implode(' ' . $this->boolean . ' ', $parts), $params];
    }

    private function compileTail(): string
    {
        $sql = [] === $this->orders ? '' : ' ORDER BY ' . implode(', ', $this->orders);
        if (null !== $this->limit) {
            $sql .= ' LIMIT ' . $this->offset . ', ' . $this->limit;
        }
        return $sql;
    }

    private function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof BackedEnum => $value->value,
            true === $value => 1,
            false === $value => 0,
            is_array($value) => json_encode($value),
            default => $value,
        };
    }
}
