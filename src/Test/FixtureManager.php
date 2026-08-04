<?php

declare(strict_types=1);

namespace Yakamara\YForm\Test;

use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use Redaxo\Core\Filesystem\Path;
use Throwable;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\Exception\FixtureException;

use function is_array;
use function is_string;

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

    public function __construct(
        private readonly string $prefix,
    ) {}

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
     * matches the Api::setTableField() shape.
     *
     * @param array<int, array<string, mixed>> $fields
     */
    public function createTable(string $shortName, array $fields = [], array $tableOptions = []): Table
    {
        $tableName = $this->reserveTableName($shortName);

        // 1. SQL schema (id + created/updated standard columns)
        DbTable::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('createdate', 'datetime', true))
            ->ensureColumn(new Column('updatedate', 'datetime', true))
            ->ensure();

        // 2. YForm metadata
        Api::setTable(array_merge([
            'table_name' => $tableName,
            'name' => $shortName,
            'status' => 1,
            'hidden' => 1,
            'prio' => 9999,
        ], $tableOptions));

        // 3. Fields
        foreach ($fields as $field) {
            Api::setTableField($tableName, $field);
        }

        $this->createdTables[] = $tableName;
        Table::deleteCache();

        $table = Table::get($tableName);
        if (null === $table) {
            throw new FixtureException('Fixture table was not registered after setTable: ' . $tableName);
        }
        return $table;
    }

    /**
     * Loads a JSON tableset, rewriting table names so they sit under the
     * suite's prefix. Returns the first table from the set.
     */
    public function loadFromJson(string $relativePath): Table
    {
        $path = Path::addon('yform', 'tests/fixtures/' . $relativePath);
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
            $def['table']['hidden'] = 1;
            foreach ($def['fields'] as &$f) {
                if (isset($f['table']) && is_string($f['table']) && str_starts_with($f['table'], 'rex_')) {
                    $f['table'] = $this->prefix . substr($f['table'], 4);
                }
            }
            unset($f);
            $rewritten[$newName] = $def;
            $this->createdTables[] = $newName;
        }

        Api::importTablesets((string) json_encode($rewritten));
        Table::deleteCache();

        $firstName = (string) array_key_first($rewritten);
        $table = Table::get($firstName);
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
                Api::removeTable($tableName);
            } catch (Throwable) {
            }
            try {
                DbTable::get($tableName)->drop();
            } catch (Throwable) {
            }
        }
        $this->createdTables = [];

        // Catch orphans from earlier crashed runs.
        try {
            $orphans = Sql::factory()->getArray(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE :p',
                [':p' => $this->prefix . '%'],
            );
            foreach ($orphans as $row) {
                $name = (string) $row['TABLE_NAME'];
                try {
                    Api::removeTable($name);
                } catch (Throwable) {
                }
                try {
                    DbTable::get($name)->drop();
                } catch (Throwable) {
                }
            }
        } catch (Throwable) {
        }

        // Drop any orphan rows in rex_yform_field that reference our prefix.
        try {
            Sql::factory()->setQuery(
                'DELETE FROM ' . Field::table() . ' WHERE table_name LIKE :p',
                [':p' => $this->prefix . '%'],
            );
        } catch (Throwable) {
        }

        Table::deleteCache();
    }

    /**
     * @return list<string>
     */
    public function leftBehind(): array
    {
        return $this->createdTables;
    }
}
