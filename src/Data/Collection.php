<?php

namespace Yakamara\YForm\Data;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use Traversable;

use function array_filter;
use function array_map;
use function array_values;
use function count;

/**
 * Immutable list of records.
 *
 * @template T of Record
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class Collection implements IteratorAggregate, Countable
{
    /** @param list<T> $records */
    public function __construct(
        private array $records = [],
    ) {}

    /** @return Traversable<int, T> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->records);
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function isEmpty(): bool
    {
        return [] === $this->records;
    }

    /** @return T|null */
    public function first(): ?Record
    {
        return $this->records[0] ?? null;
    }

    /** @return list<T> */
    public function toArray(): array
    {
        return $this->records;
    }

    /**
     * @param Closure(T): bool $callback
     *
     * @return self<T>
     */
    public function filter(Closure $callback): self
    {
        return new self(array_values(array_filter($this->records, $callback)));
    }

    /**
     * @template R
     *
     * @param Closure(T): R $callback
     *
     * @return list<R>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->records);
    }

    /** @return list<int> */
    public function ids(): array
    {
        return array_map(static fn (Record $record): int => (int) $record->id, $this->records);
    }

    /** @return list<mixed> */
    public function pluck(string $field): array
    {
        return array_map(static fn (Record $record): mixed => $record->get($field), $this->records);
    }

    /** @return array<int|string, mixed> e.g. `->toKeyValue('id', 'name')` for select options */
    public function toKeyValue(string $keyField, string $valueField): array
    {
        $result = [];
        foreach ($this->records as $record) {
            $result[(string) $record->get($keyField)] = $record->get($valueField);
        }
        return $result;
    }

    /** @return array<int|string, self<T>> */
    public function groupBy(string $field): array
    {
        $groups = [];
        foreach ($this->records as $record) {
            $groups[(string) $record->get($field)][] = $record;
        }
        return array_map(static fn (array $records): self => new self($records), $groups);
    }
}
