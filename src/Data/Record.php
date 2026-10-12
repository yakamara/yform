<?php

namespace Yakamara\YForm\Data;

use ArrayAccess;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Field\Relation\Relation;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Validation\Errors;
use Yakamara\YForm\Validation\ValidationException;

use function array_key_exists;
use function sprintf;

/**
 * One row of a managed table with PHP typed values (dates as `DateTimeImmutable`, booleans, lists, …).
 *
 * Tables may register a subclass as model (`#[AsTable(model: Product::class)]`) and add typed accessors:
 *
 * ```php
 * final class Product extends Record
 * {
 *     public string $name { get => $this->get('name'); set => $this->set('name', $value); }
 * }
 * ```
 *
 * @implements ArrayAccess<string, mixed>
 */
class Record implements ArrayAccess, JsonSerializable
{
    public private(set) ?int $id;

    /** @var array<string, mixed> */
    private array $values;

    /** @var array<string, mixed> values as loaded/last saved */
    private array $original;

    /**
     * @internal use `Table::create()` / `Table::find()`
     *
     * @param array<string, mixed> $values
     */
    final public function __construct(
        public readonly Table $table,
        array $values,
        ?int $id,
    ) {
        $this->values = $values;
        $this->original = null === $id ? [] : $values;
        $this->id = $id;
    }

    public function get(string $field): mixed
    {
        if ('id' === $field) {
            return $this->id;
        }
        if (!array_key_exists($field, $this->values)) {
            throw new InvalidArgumentException(sprintf('Table "%s" has no stored field "%s".', $this->table->name, $field));
        }
        if (Unloaded::Value === $this->values[$field]) {
            $value = $this->table->getField($field)->loadValue($this);
            $this->values[$field] = $value;
            $this->original[$field] = $value;
        }
        return $this->values[$field];
    }

    /** Whether a lazily loaded value (relation) has been loaded or set. */
    public function isLoaded(string $field): bool
    {
        return Unloaded::Value !== ($this->values[$field] ?? null);
    }

    /**
     * Related record(s) of a relation field: a record for `BelongsTo`, a collection for `HasMany`/`ManyToMany`.
     *
     * @return Record|Collection<Record>|null
     */
    public function related(string $field): self|Collection|null
    {
        $relation = $this->table->getField($field);
        if (!$relation instanceof Relation) {
            throw new InvalidArgumentException(sprintf('Field "%s" of table "%s" is no relation.', $field, $this->table->name));
        }
        return $relation->related($this);
    }

    public function set(string $field, mixed $value): static
    {
        if (!array_key_exists($field, $this->values)) {
            throw new InvalidArgumentException(sprintf('Table "%s" has no stored field "%s".', $this->table->name, $field));
        }
        $this->values[$field] = $value;
        return $this;
    }

    public function has(string $field): bool
    {
        return 'id' === $field || array_key_exists($field, $this->values);
    }

    /** @return array<string, mixed> all values (lazy relation values are loaded) */
    public function toArray(): array
    {
        $values = ['id' => $this->id];
        foreach ($this->values as $field => $value) {
            $values[$field] = $this->get($field);
        }
        return $values;
    }

    /**
     * Unsaved copy of the record. Each field decides what the copy takes over ({@see Field::cloneValue()}):
     * timestamps, UUIDs and passwords start empty, inline has-many rows are copied as new records.
     */
    public function replicate(): self
    {
        $copy = $this->table->newRecord();
        foreach ($this->values as $name => $value) {
            $copy->set($name, $this->table->getField($name)->cloneValue($this->get($name), $this));
        }
        return $copy;
    }

    public function isNew(): bool
    {
        return null === $this->id;
    }

    /** @return array<string, mixed> original value per changed field */
    public function changes(): array
    {
        $changes = [];
        foreach ($this->values as $field => $value) {
            if (Unloaded::Value === $value) {
                continue;
            }
            $original = $this->original[$field] ?? null;
            if (Unloaded::Value === $original) {
                $changes[$field] = null;
                continue;
            }
            if ($value != $original || (null === $value) !== (null === $original)) {
                $changes[$field] = $original;
            }
        }
        return $changes;
    }

    public function label(): string
    {
        return $this->table->recordLabel($this);
    }

    public function validate(): Errors
    {
        return $this->table->validateRecord($this);
    }

    /** @throws ValidationException */
    public function save(): static
    {
        $this->table->save($this);
        return $this;
    }

    public function delete(): void
    {
        $this->table->delete($this);
    }

    /** @internal */
    final public function markSaved(int $id): void
    {
        if (null !== $this->id && $this->id !== $id) {
            throw new LogicException('The id of a record can not change.');
        }
        $this->id = $id;
        $this->original = $this->values;
    }

    /** @internal */
    final public function markDeleted(): void
    {
        $this->id = null;
        $this->original = [];
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name) && null !== $this->get($name);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Fields of a record can not be removed.');
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
