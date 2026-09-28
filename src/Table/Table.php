<?php

namespace Yakamara\YForm\Table;

use Generator;
use InvalidArgumentException;
use LogicException;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Attribute\AsTable;
use Yakamara\YForm\Data\Collection;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Data\Unloaded;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\Field\Relation\References;
use Yakamara\YForm\Field\Text;
use Yakamara\YForm\Form\Layout\Layout;
use Yakamara\YForm\Validation\Errors;
use Yakamara\YForm\Validation\ValidationContext;
use Yakamara\YForm\Validation\ValidationException;

use function assert;
use function sprintf;

/**
 * Base class of a managed table. The subclass is the complete definition: fields, form layout, list view and
 * behaviour. Nothing is stored in the database except the data itself.
 *
 * ```php
 * #[AsTable('rex_product', label: 'Produkte')]
 * final class ProductTable extends Table
 * {
 *     public function fields(): iterable
 *     {
 *         yield Text::new('name')->label('Name')->required();
 *         yield Decimal::new('price')->label('Preis')->min(0);
 *     }
 * }
 *
 * $products = ProductTable::query()->where('price', '>', 10)->orderBy('name')->find();
 * ```
 */
abstract class Table
{
    /** Database table name, e.g. `rex_product`; also used in URLs and permissions. */
    public readonly string $name;

    /** @var array<string, Field>|null */
    private ?array $fieldCache = null;

    final public function __construct(
        public readonly AsTable $definition,
    ) {
        $this->name = $definition->name;
    }

    /**
     * The fields of the table in their default order. Called once per request and table.
     *
     * @return iterable<Field>
     */
    abstract public function fields(): iterable;

    /**
     * Default form layout. A layout saved with the backend designer (`ClassName.layout.json` next to this
     * class) takes precedence; fields that are not placed anywhere are appended automatically.
     */
    public function layout(Layout $layout): Layout
    {
        return $layout;
    }

    /** Backend list configuration (columns, sorting, page size, custom cell rendering). */
    public function list(ListConfig $list): ListConfig
    {
        return $list;
    }

    /** Cross-field validation; add messages to `$errors`. */
    public function validate(Record $record, Errors $errors): void {}

    public function beforeSave(Record $record, bool $isNew): void {}

    public function afterSave(Record $record, bool $isNew): void {}

    /** Throw an exception to prevent the deletion. */
    public function beforeDelete(Record $record): void {}

    public function afterDelete(Record $record): void {}

    /** Human readable label of a record (relations, headlines). Default: value of the first text field. */
    public function recordLabel(Record $record): string
    {
        foreach ($this->getFields() as $field) {
            if ($field instanceof Text && $field->storesValue()) {
                $label = $field->textValue($record->get($field->name));
                if ('' !== $label) {
                    return $label;
                }
            }
        }
        return '#' . $record->id;
    }

    // ---------------------------------------------------------------- metadata

    public function label(): string
    {
        return I18n::translate($this->definition->label ?? $this->name, false);
    }

    public function icon(): string
    {
        return $this->definition->icon ?? 'fa-table';
    }

    /** @return array<string, Field> */
    final public function getFields(): array
    {
        if (null !== $this->fieldCache) {
            return $this->fieldCache;
        }

        $fields = [];
        foreach ($this->fields() as $field) {
            if ('id' === $field->name) {
                throw new LogicException(sprintf('Table "%s": the field name "id" is reserved for the primary key.', static::class));
            }
            if (isset($fields[$field->name])) {
                throw new LogicException(sprintf('Table "%s" defines the field "%s" twice.', static::class, $field->name));
            }
            $fields[$field->name] = $field;
        }
        return $this->fieldCache = $fields;
    }

    final public function hasField(string $name): bool
    {
        return isset($this->getFields()[$name]);
    }

    final public function getField(string $name): Field
    {
        return $this->getFields()[$name] ?? throw new InvalidArgumentException(sprintf('Table "%s" has no field "%s".', $this->name, $name));
    }

    /** @return Generator<string, Field> fields backed by columns */
    final public function storedFields(): Generator
    {
        foreach ($this->getFields() as $name => $field) {
            if ($field->storesValue()) {
                yield $name => $field;
            }
        }
    }

    /** Whether the column exists in the definition (`id` or a column of a stored field). */
    final public function hasColumn(string $column): bool
    {
        if ('id' === $column) {
            return true;
        }
        foreach ($this->storedFields() as $field) {
            foreach ($field->columns() as $col) {
                if ($col->name === $column) {
                    return true;
                }
            }
        }
        return false;
    }

    // ---------------------------------------------------------------- static access

    /** The registered instance of this table class. */
    public static function table(): static
    {
        $table = Tables::get(static::class);
        assert($table instanceof static);
        return $table;
    }

    public static function query(): Query
    {
        return new Query(static::table());
    }

    public static function find(int $id): ?Record
    {
        return static::query()->where('id', $id)->first();
    }

    public static function require(int $id): Record
    {
        return static::find($id) ?? throw new RecordNotFound(sprintf('Record %d not found in table "%s".', $id, static::table()->name));
    }

    /** @param array<string, mixed> $values */
    public static function create(array $values = []): Record
    {
        $table = static::table();
        $record = $table->newRecord();
        foreach ($values as $name => $value) {
            $record->set($name, $value);
        }
        return $record;
    }

    /** @return Collection<Record> */
    public static function all(): Collection
    {
        return static::query()->find();
    }

    // ---------------------------------------------------------------- persistence

    /** @internal Builds a record from a DB row. */
    final public function hydrate(array $row): Record
    {
        $values = [];
        foreach ($this->storedFields() as $name => $field) {
            // Fields without own column (relations) load their value on first access.
            $values[$name] = [] === $field->columns() ? Unloaded::Value : $field->fromDb($row[$name] ?? null);
        }
        return $this->instantiate($values, (int) $row['id']);
    }

    final public function newRecord(): Record
    {
        $values = [];
        foreach ($this->storedFields() as $name => $field) {
            $values[$name] = $field->defaultValue();
        }
        return $this->instantiate($values, null);
    }

    /** @param array<string, mixed> $values */
    private function instantiate(array $values, ?int $id): Record
    {
        $class = $this->definition->model ?? Record::class;
        return new $class($this, $values, $id);
    }

    final public function validateRecord(Record $record): Errors
    {
        $errors = new Errors();
        $context = new ValidationContext($record->toArray(), $this, $record);
        foreach ($this->storedFields() as $name => $field) {
            foreach ($field->validate($record->get($name), $context) as $message) {
                $errors->add($name, $message);
            }
        }
        $this->validate($record, $errors);

        return Extension::dispatch(new ExtensionPoint('YFORM_RECORD_VALIDATE', $errors, ['table' => $this, 'record' => $record]));
    }

    /**
     * Validates and saves the record in a transaction.
     *
     * @throws ValidationException
     */
    final public function save(Record $record): void
    {
        $isNew = null === $record->id;

        foreach ($this->getFields() as $field) {
            $field->beforeSave($record, $isNew);
        }

        $errors = $this->validateRecord($record);
        if (!$errors->isEmpty()) {
            throw new ValidationException($errors);
        }

        $this->beforeSave($record, $isNew);
        Extension::dispatch(new ExtensionPoint('YFORM_RECORD_SAVE', $record, ['table' => $this, 'is_new' => $isNew]));

        $sql = Sql::factory();
        $sql->transactional(function () use ($record, $isNew): void {
            $sql = Sql::factory()->setTable($this->name);
            foreach ($this->storedFields() as $name => $field) {
                if ([] === $field->columns()) {
                    continue;
                }
                foreach ($field->toDb($record->get($name)) as $column => $value) {
                    $sql->setValue($column, $value);
                }
            }
            if ($isNew) {
                $sql->insert();
                $record->markSaved($sql->getLastId());
            } else {
                $sql->setWhere(['id' => $record->id])->update();
                $record->markSaved((int) $record->id);
            }

            foreach ($this->getFields() as $field) {
                $field->afterSave($record, $isNew);
            }
        });

        $this->afterSave($record, $isNew);
        Extension::dispatch(new ExtensionPoint('YFORM_RECORD_SAVED', $record, ['table' => $this, 'is_new' => $isNew]));
    }

    final public function delete(Record $record): void
    {
        if (null === $record->id) {
            return;
        }

        $this->beforeDelete($record);
        Extension::dispatch(new ExtensionPoint('YFORM_RECORD_DELETE', $record, ['table' => $this]));

        Sql::factory()->transactional(function () use ($record): void {
            References::beforeDelete($this, $record);
            foreach ($this->getFields() as $field) {
                $field->beforeDelete($record);
            }
            Sql::factory()->setTable($this->name)->setWhere(['id' => $record->id])->delete();
        });

        $this->afterDelete($record);
        Extension::dispatch(new ExtensionPoint('YFORM_RECORD_DELETED', $record, ['table' => $this]));
        $record->markDeleted();
    }
}
