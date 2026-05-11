<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use ReflectionClass;
use rex_path;
use rex_sql;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

use function count;
use function in_array;

/**
 * Tests for the rex_yform_manager_table cache layer.
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
        return rex_path::pluginCache('yform', 'manager', 'tables.cache');
    }

    private function getStaticCache(): mixed
    {
        $ref = new ReflectionClass(rex_yform_manager_table::class);
        $prop = $ref->getProperty('cache');
        return $prop->getValue();
    }

    public function testDeleteCacheClearsStaticAndFile(): void
    {
        // Force population.
        rex_yform_manager_table::getAll();
        $this->assertNotNull($this->getStaticCache(), 'Static cache must be populated after getAll().');

        rex_yform_manager_table::deleteCache();

        $this->assertNull($this->getStaticCache(), 'deleteCache() must null the static cache.');
        $this->assertFalse(is_file($this->cachePath()), 'deleteCache() must remove the file cache.');
    }

    public function testGetReturnsCachedInstanceAcrossCalls(): void
    {
        $table = $this->fixtures->createTable('cache_inst');

        $a = rex_yform_manager_table::get($table->getTableName());
        $b = rex_yform_manager_table::get($table->getTableName());

        // Same in-process call returns the same instance (rex_yform_manager_table::$tables map).
        $this->assertSame($a, $b);
    }

    public function testSetTableAutoInvalidatesCache(): void
    {
        $table = $this->fixtures->createTable('cache_auto', [], ['name' => 'OldName']);
        $tableName = $table->getTableName();

        // Mutate via API — must bust the cache implicitly.
        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name' => 'NewName',
            'status' => 1,
            'hidden' => 1,
        ]);

        $reloaded = rex_yform_manager_table::get($tableName);
        $this->assertNotNull($reloaded);
        $this->assertSame('NewName', $reloaded->getName());
    }

    public function testCacheFileExistsAfterFirstLookup(): void
    {
        // Ensure clean start so the file is rebuilt.
        rex_yform_manager_table::deleteCache();

        rex_yform_manager_table::getAll();

        $this->assertTrue(is_file($this->cachePath()), 'tables.cache must exist after getAll().');
    }

    public function testGetAllIncludesFreshlyAddedTables(): void
    {
        $names = array_map(
            static fn (rex_yform_manager_table $t) => $t->getTableName(),
            array_values(rex_yform_manager_table::getAll()),
        );
        $before = count($names);

        $table = $this->fixtures->createTable('cache_new');
        $afterNames = array_map(
            static fn (rex_yform_manager_table $t) => $t->getTableName(),
            array_values(rex_yform_manager_table::getAll()),
        );

        $this->assertSame($before + 1, count($afterNames));
        $this->assertTrue(in_array($table->getTableName(), $afterNames, true));
    }

    public function testGetForUnknownTableReturnsNullAndDoesNotPoisonCache(): void
    {
        $missing = 'unittest_definitely_not_a_table_' . uniqid();
        $this->assertNull(rex_yform_manager_table::get($missing));
        $this->assertNull(rex_yform_manager_table::get($missing));
        // Sanity: an existing fixture still resolves.
        $fixture = $this->fixtures->createTable('cache_after_miss');
        $this->assertNotNull(rex_yform_manager_table::get($fixture->getTableName()));
    }

    public function testGetCacheHandlesYformRowWithMissingSqlTable(): void
    {
        // Insert a rex_yform_table row whose underlying SQL table doesn't exist.
        // rex_yform_manager_table::getCache() catches the SHOW COLUMNS failure
        // and leaves the columns list empty rather than crashing.
        $ghostName = $this->fixtures->reserveTableName('ghost');

        rex_sql::factory()
            ->setTable(rex_yform_manager_table::table())
            ->setValue('table_name', $ghostName)
            ->setValue('name', 'Ghost')
            ->setValue('status', 1)
            ->setValue('hidden', 1)
            ->insert();

        try {
            rex_yform_manager_table::deleteCache();

            // Must not throw.
            $all = rex_yform_manager_table::getAll();
            $names = array_map(static fn (rex_yform_manager_table $t) => $t->getTableName(), array_values($all));
            $this->assertTrue(in_array($ghostName, $names, true), 'Ghost row should appear in the metadata listing.');

            $ghost = rex_yform_manager_table::require($ghostName);
            $this->assertCount(0, $ghost->getColumns(), 'Missing SQL table means getColumns() is empty.');
        } finally {
            rex_sql::factory()->setQuery(
                'DELETE FROM ' . rex_yform_manager_table::table() . ' WHERE table_name = :n',
                [':n' => $ghostName],
            );
            rex_yform_manager_table::deleteCache();
        }
    }
}
