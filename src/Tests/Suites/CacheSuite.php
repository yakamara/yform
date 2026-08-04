<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\Path;
use ReflectionClass;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;

use function count;
use function in_array;

/**
 * Tests for the Table cache layer.
 * Covers §3.U.7 from .claude/plans/02-test-strategy.md.
 *
 * The cache has two stages:
 *   - file: cache/addons/yform/manager/tables.cache
 *   - static $cache + $tables: in-memory map(s) populated from the file
 *
 * deleteCache() must clear both. setTable / setTableField call it automatically.
 *
 * @package redaxo\yform
 * @internal
 */
final class CacheSuite extends AbstractTestSuite
{
    private function cachePath(): string
    {
        return Path::addonCache('yform', 'manager.tables.cache');
    }

    private function getStaticCache(): mixed
    {
        $ref = new ReflectionClass(Table::class);
        $prop = $ref->getProperty('cache');
        return $prop->getValue();
    }

    public function testDeleteCacheClearsStaticAndFile(): void
    {
        // Force population.
        Table::getAll();
        $this->assertNotNull($this->getStaticCache(), 'Static cache must be populated after getAll().');

        Table::deleteCache();

        $this->assertNull($this->getStaticCache(), 'deleteCache() must null the static cache.');
        $this->assertFalse(is_file($this->cachePath()), 'deleteCache() must remove the file cache.');
    }

    public function testGetReturnsCachedInstanceAcrossCalls(): void
    {
        $table = $this->fixtures->createTable('cache_inst');

        $a = Table::get($table->getTableName());
        $b = Table::get($table->getTableName());

        // Same in-process call returns the same instance (Table::$tables map).
        $this->assertSame($a, $b);
    }

    public function testSetTableAutoInvalidatesCache(): void
    {
        $table = $this->fixtures->createTable('cache_auto', [], ['name' => 'OldName']);
        $tableName = $table->getTableName();

        // Mutate via API — must bust the cache implicitly.
        Api::setTable([
            'table_name' => $tableName,
            'name' => 'NewName',
            'status' => 1,
            'hidden' => 1,
        ]);

        $reloaded = Table::get($tableName);
        $this->assertNotNull($reloaded);
        $this->assertSame('NewName', $reloaded->getName());
    }

    public function testCacheFileExistsAfterFirstLookup(): void
    {
        // Ensure clean start so the file is rebuilt.
        Table::deleteCache();

        Table::getAll();

        $this->assertTrue(is_file($this->cachePath()), 'tables.cache must exist after getAll().');
    }

    public function testGetAllIncludesFreshlyAddedTables(): void
    {
        $names = array_map(
            static fn (Table $t) => $t->getTableName(),
            array_values(Table::getAll()),
        );
        $before = count($names);

        $table = $this->fixtures->createTable('cache_new');
        $afterNames = array_map(
            static fn (Table $t) => $t->getTableName(),
            array_values(Table::getAll()),
        );

        $this->assertSame($before + 1, count($afterNames));
        $this->assertTrue(in_array($table->getTableName(), $afterNames, true));
    }

    public function testGetForUnknownTableReturnsNullAndDoesNotPoisonCache(): void
    {
        $missing = 'unittest_definitely_not_a_table_' . uniqid();
        $this->assertNull(Table::get($missing));
        $this->assertNull(Table::get($missing));
        // Sanity: an existing fixture still resolves.
        $fixture = $this->fixtures->createTable('cache_after_miss');
        $this->assertNotNull(Table::get($fixture->getTableName()));
    }

    public function testGetCacheHandlesYformRowWithMissingSqlTable(): void
    {
        // Insert a rex_yform_table row whose underlying SQL table doesn't exist.
        // Table::getCache() catches the SHOW COLUMNS failure
        // and leaves the columns list empty rather than crashing.
        $ghostName = $this->fixtures->reserveTableName('ghost');

        Sql::factory()
            ->setTable(Table::table())
            ->setValue('table_name', $ghostName)
            ->setValue('name', 'Ghost')
            ->setValue('status', 1)
            ->setValue('hidden', 1)
            ->insert();

        try {
            Table::deleteCache();

            // Must not throw.
            $all = Table::getAll();
            $names = array_map(static fn (Table $t) => $t->getTableName(), array_values($all));
            $this->assertTrue(in_array($ghostName, $names, true), 'Ghost row should appear in the metadata listing.');

            $ghost = Table::require($ghostName);
            $this->assertCount(0, $ghost->getColumns(), 'Missing SQL table means getColumns() is empty.');
        } finally {
            Sql::factory()->setQuery(
                'DELETE FROM ' . Table::table() . ' WHERE table_name = :n',
                [':n' => $ghostName],
            );
            Table::deleteCache();
        }
    }
}
