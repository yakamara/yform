<?php

declare(strict_types=1);

namespace Redaxo\YForm\Test;

use Redaxo\YForm\Test\Exception\FixtureException;
use rex_path;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform_manager_field;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use Throwable;

/**
 * Test-table lifecycle. Creates tables under a unique prefix per suite run,
 * tears them down in cleanup(). Robust against partial leftovers from
 * aborted previous runs (cleanup() also drops any table matching the prefix).
 *
 * @package redaxo\yform
 * @internal
 */
final class FixtureManager
{
    /** @var list<string> */
    private array $createdTables = [];

    public function __construct(private readonly string $prefix) {}

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Returns the full table name for a short identifier.
     */
    public function reserveTableName(string $shortName): string
    {
        return $this->prefix . preg_replace('/[^a-z0-9_]/i', '_', $shortName);
    }

    /**
     * Creates a fixture table with the given YForm fields. Each field array
     * matches the rex_yform_manager_table_api::setTableField() shape.
     *
     * @param array<int, array<string, mixed>> $fields
     */
    public function createTable(string $shortName, array $fields = [], array $tableOptions = []): rex_yform_manager_table
    {
        $tableName = $this->reserveTableName($shortName);

        // 1. SQL schema (id + created/updated standard columns)
        rex_sql_table::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('createdate', 'datetime', true))
            ->ensureColumn(new rex_sql_column('updatedate', 'datetime', true))
            ->ensure();

        // 2. YForm metadata
        rex_yform_manager_table_api::setTable(array_merge([
            'table_name' => $tableName,
            'name'       => $shortName,
            'status'     => 1,
            'hidden'     => 1,
            'prio'       => 9999,
        ], $tableOptions));

        // 3. Fields
        foreach ($fields as $field) {
            rex_yform_manager_table_api::setTableField($tableName, $field);
        }

        $this->createdTables[] = $tableName;
        rex_yform_manager_table::deleteCache();

        $table = rex_yform_manager_table::get($tableName);
        if (null === $table) {
            throw new FixtureException('Fixture table was not registered after setTable: ' . $tableName);
        }
        return $table;
    }

    /**
     * Loads a JSON tableset, rewriting table names so they sit under the
     * suite's prefix. Returns the first table from the set.
     */
    public function loadFromJson(string $relativePath): rex_yform_manager_table
    {
        $path = rex_path::addon('yform', 'tests/fixtures/' . $relativePath);
        $json = @file_get_contents($path);
        if (false === $json) {
            throw new FixtureException('Fixture not found: ' . $relativePath);
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new FixtureException('Fixture is not valid JSON: ' . $relativePath);
        }

        $rewritten = [];
        foreach ($data as $originalName => $def) {
            if (!is_array($def) || !isset($def['table'], $def['fields'])) {
                throw new FixtureException('Fixture entry malformed: ' . $originalName);
            }
            $newName = $this->prefix . preg_replace('/^rex_/', '', (string) $originalName);
            $def['table']['table_name'] = $newName;
            $def['table']['hidden']     = 1;
            foreach ($def['fields'] as &$f) {
                if (isset($f['table']) && is_string($f['table']) && str_starts_with($f['table'], 'rex_')) {
                    $f['table'] = $this->prefix . substr($f['table'], 4);
                }
            }
            unset($f);
            $rewritten[$newName] = $def;
            $this->createdTables[] = $newName;
        }

        rex_yform_manager_table_api::importTablesets((string) json_encode($rewritten));
        rex_yform_manager_table::deleteCache();

        $firstName = (string) array_key_first($rewritten);
        $table = rex_yform_manager_table::get($firstName);
        if (null === $table) {
            throw new FixtureException('Fixture failed to import: ' . $firstName);
        }
        return $table;
    }

    /**
     * Drops all tracked fixture tables plus any orphans matching the prefix.
     */
    public function cleanup(): void
    {
        foreach ($this->createdTables as $tableName) {
            try {
                rex_yform_manager_table_api::removeTable($tableName);
            } catch (Throwable) {}
            try {
                rex_sql_table::get($tableName)->drop();
            } catch (Throwable) {}
        }
        $this->createdTables = [];

        // Catch orphans from earlier crashed runs.
        try {
            $orphans = rex_sql::factory()->getArray(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE :p',
                [':p' => $this->prefix . '%'],
            );
            foreach ($orphans as $row) {
                $name = (string) $row['TABLE_NAME'];
                try {
                    rex_yform_manager_table_api::removeTable($name);
                } catch (Throwable) {}
                try {
                    rex_sql_table::get($name)->drop();
                } catch (Throwable) {}
            }
        } catch (Throwable) {}

        // Drop any orphan rows in rex_yform_field that reference our prefix.
        try {
            rex_sql::factory()->setQuery(
                'DELETE FROM ' . rex_yform_manager_field::table() . ' WHERE table_name LIKE :p',
                [':p' => $this->prefix . '%'],
            );
        } catch (Throwable) {}

        rex_yform_manager_table::deleteCache();
    }

    /**
     * @return list<string>
     */
    public function leftBehind(): array
    {
        return $this->createdTables;
    }
}
