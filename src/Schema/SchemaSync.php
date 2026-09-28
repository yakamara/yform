<?php

namespace Yakamara\YForm\Schema;

use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Index;
use Redaxo\Core\Database\Table as DbTable;
use Yakamara\YForm\Table\Table;
use Yakamara\YForm\Table\Tables;

use function in_array;

/**
 * Brings the database tables in line with the table definitions.
 *
 * Tables, columns and indexes are created and changed automatically; the column order follows the field order.
 * Columns without a field are never dropped implicitly (that destroys data) — pass them explicitly to
 * {@see apply()}.
 */
final class SchemaSync
{
    public static function plan(Table $table): SchemaPlan
    {
        $dbTable = DbTable::get($table->name);
        if (!$dbTable->exists()) {
            return new SchemaPlan($table->name, true, additional: self::planAdditional($table));
        }

        $add = [];
        $modify = [];
        $desired = ['id' => true];
        foreach (self::columns($table) as $column) {
            $desired[$column->name] = true;
            $existing = $dbTable->getColumn($column->name);
            if (null === $existing) {
                $add[] = $column->name;
            } elseif (!$existing->equals($column)) {
                $modify[] = $column->name;
            }
        }

        $indexes = [];
        foreach (self::indexes($table) as $index) {
            $existing = $dbTable->getIndex($index->name);
            if (null === $existing || !$existing->equals($index)) {
                $indexes[] = $index->name;
            }
        }

        $obsolete = [];
        foreach ($dbTable->getColumns() as $name => $column) {
            if (!isset($desired[$name])) {
                $obsolete[] = $name;
            }
        }

        return new SchemaPlan($table->name, false, $add, $modify, $indexes, $obsolete, self::planAdditional($table));
    }

    /** @return list<string> additional tables (pivot tables) that are missing or incomplete */
    private static function planAdditional(Table $table): array
    {
        $pending = [];
        foreach (self::additionalTables($table) as $schema) {
            $dbTable = DbTable::get($schema->name);
            if (!$dbTable->exists()) {
                $pending[] = $schema->name;
                continue;
            }
            foreach ($schema->columns as $column) {
                $existing = $dbTable->getColumn($column->name);
                if (null === $existing || !$existing->equals($column)) {
                    $pending[] = $schema->name;
                    continue 2;
                }
            }
        }
        return $pending;
    }

    /** @return list<TableSchema> */
    private static function additionalTables(Table $table): array
    {
        $schemas = [];
        foreach ($table->getFields() as $field) {
            foreach ($field->additionalTables($table) as $schema) {
                $schemas[] = $schema;
            }
        }
        return $schemas;
    }

    /** @param list<string> $drop obsolete columns to drop (must be part of the plan's obsolete columns) */
    public static function apply(Table $table, array $drop = []): SchemaPlan
    {
        $plan = self::plan($table);

        $dbTable = DbTable::get($table->name);
        $dbTable->ensurePrimaryIdColumn();
        foreach (self::columns($table) as $column) {
            $dbTable->ensureColumn($column);
        }
        foreach (self::indexes($table) as $index) {
            $dbTable->ensureIndex($index);
        }
        foreach ($drop as $column) {
            if (in_array($column, $plan->obsolete, true)) {
                $dbTable->removeColumn($column);
            }
        }
        $dbTable->ensure();
        DbTable::clearInstance($table->name);

        foreach (self::additionalTables($table) as $schema) {
            $extra = DbTable::get($schema->name);
            foreach ($schema->columns as $column) {
                $extra->ensureColumn($column);
            }
            $extra->setPrimaryKey($schema->primaryKey);
            foreach ($schema->indexes as $index) {
                $extra->ensureIndex($index);
            }
            $extra->ensure();
            DbTable::clearInstance($schema->name);
        }

        return $plan;
    }

    /**
     * Applies all table definitions without dropping anything (used by `console migrate`).
     *
     * @return list<SchemaPlan> plans that contained changes
     */
    public static function applyAll(): array
    {
        $changed = [];
        foreach (Tables::all() as $table) {
            $plan = self::apply($table);
            if ($plan->hasChanges()) {
                $changed[] = $plan;
            }
        }
        return $changed;
    }

    /** @return list<Column> */
    private static function columns(Table $table): array
    {
        $columns = [];
        foreach ($table->storedFields() as $field) {
            foreach ($field->columns() as $column) {
                $columns[] = $column;
            }
        }
        return $columns;
    }

    /** @return list<Index> */
    private static function indexes(Table $table): array
    {
        $indexes = [];
        foreach ($table->getFields() as $field) {
            foreach ($field->indexes() as $index) {
                $indexes[] = $index;
            }
        }
        return $indexes;
    }
}
