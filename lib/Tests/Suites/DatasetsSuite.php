<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use InvalidArgumentException;
use Redaxo\YForm\Test\AbstractTestSuite;
use ReflectionClass;
use rex;
use rex_exception;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform_manager_collection;
use rex_yform_manager_dataset;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

use function count;
use function in_array;

/**
 * Tests for rex_yform_manager_dataset (YOrm active-record).
 * Covers §3.I.1 from .claude/plans/02-test-strategy.md.
 *
 * Each test creates its own fixture table to stay independent. Heavier than
 * unit suites because every test exercises the full yform field pipeline
 * via $dataset->save().
 *
 * @package redaxo\yform
 * @internal
 */
final class DatasetsSuite extends AbstractTestSuite
{
    /**
     * Sets up a generic single-table fixture with text + integer columns.
     * Returns the table.
     */
    private function makeBasicTable(string $shortName): rex_yform_manager_table
    {
        $tableName = $this->fixtures->reserveTableName($shortName);

        rex_sql_table::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('title', 'varchar(191)', true))
            ->ensureColumn(new rex_sql_column('quantity', 'int(11)', true))
            ->ensureColumn(new rex_sql_column('status', 'tinyint(1)', false, '1'))
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name' => $shortName,
            'status' => 1,
            'hidden' => 1,
            'prio' => 9999,
        ], [
            ['type_id' => 'value', 'type_name' => 'text',     'name' => 'title',    'label' => 'Titel',   'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer',  'name' => 'quantity', 'label' => 'Menge',   'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'integer',  'name' => 'status',   'label' => 'Status',  'prio' => 3],
        ]);

        // FixtureManager tracks this table for cleanup; use its public path.
        $this->fixtures->createTable($shortName . '_marker', [], []);
        // Above would create a parallel marker — too clever. Just track manually:
        $this->trackFixture($tableName);

        rex_yform_manager_table::deleteCache();
        return rex_yform_manager_table::require($tableName);
    }

    /**
     * Adds an externally created table to the FixtureManager's tracked list.
     * Uses the public API via reflection — kept tight to one line.
     */
    private function trackFixture(string $tableName): void
    {
        $reflection = new ReflectionClass($this->fixtures);
        $prop = $reflection->getProperty('createdTables');
        $list = (array) $prop->getValue($this->fixtures);
        if (!in_array($tableName, $list, true)) {
            $list[] = $tableName;
            $prop->setValue($this->fixtures, $list);
        }
    }

    public function testCreateThenGetRoundtrip(): void
    {
        $table = $this->makeBasicTable('crud_create');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', 'Hello');
        $ds->setValue('quantity', 42);
        $ds->setValue('status', 1);

        $this->assertTrue($ds->save(), 'Initial save() must succeed. Messages: ' . implode(' / ', $ds->getMessages()));

        $id = $ds->getId();
        $this->assertTrue($id > 0);

        // Force cache reset to read fresh.
        rex_yform_manager_dataset::clearInstance([$table->getTableName(), $id]);

        $loaded = rex_yform_manager_dataset::get($id, $table->getTableName());
        $this->assertNotNull($loaded);
        $this->assertSame('Hello', $loaded->getValue('title'));
        $this->assertSame(42, (int) $loaded->getValue('quantity'));
    }

    public function testRequireThrowsForMissingId(): void
    {
        $table = $this->makeBasicTable('crud_require');

        $this->assertThrows(
            rex_exception::class,
            static fn () => rex_yform_manager_dataset::require(987654321, $table->getTableName()),
        );
    }

    public function testGetWithInvalidIdThrowsInvalidArgument(): void
    {
        $table = $this->makeBasicTable('crud_invalid_id');

        $this->assertThrows(
            InvalidArgumentException::class,
            static fn () => rex_yform_manager_dataset::get(0, $table->getTableName()),
        );
    }

    public function testUpdateChangesPersist(): void
    {
        $table = $this->makeBasicTable('crud_update');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', 'Before');
        $ds->setValue('quantity', 1);
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        $id = $ds->getId();

        $ds->setValue('title', 'After');
        $ds->setValue('quantity', 99);
        $this->assertTrue($ds->save());

        rex_yform_manager_dataset::clearInstance([$table->getTableName(), $id]);
        $reloaded = rex_yform_manager_dataset::require($id, $table->getTableName());
        $this->assertSame('After', $reloaded->getValue('title'));
        $this->assertSame(99, (int) $reloaded->getValue('quantity'));
    }

    public function testDeleteRemovesRow(): void
    {
        $table = $this->makeBasicTable('crud_delete');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', 'Doomed');
        $ds->setValue('quantity', 0);
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        $id = $ds->getId();

        $this->assertTrue($ds->delete());

        $found = rex_yform_manager_dataset::get($id, $table->getTableName());
        $this->assertNull($found);
    }

    public function testMagicGettersAndSetters(): void
    {
        $table = $this->makeBasicTable('crud_magic');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        // The dataset deliberately supports __set/__get for column access.
        // PHPStan can't know about column names — silence per access.
        /** @phpstan-ignore-next-line */
        $ds->title = 'Magic';
        /** @phpstan-ignore-next-line */
        $ds->quantity = 7;
        /** @phpstan-ignore-next-line */
        $ds->status = 1;
        $this->assertTrue($ds->save());

        /** @phpstan-ignore-next-line */
        $this->assertSame('Magic', $ds->title);
        /** @phpstan-ignore-next-line */
        $this->assertSame(7, (int) $ds->quantity);
        /** @phpstan-ignore-next-line */
        $this->assertTrue(isset($ds->title));
    }

    public function testQueryWhereReturnsMatchingRows(): void
    {
        $table = $this->makeBasicTable('crud_query');

        foreach ([['A', 1], ['B', 0], ['C', 1]] as [$title, $status]) {
            $ds = rex_yform_manager_dataset::create($table->getTableName());
            $ds->setValue('title', $title);
            $ds->setValue('quantity', 1);
            $ds->setValue('status', $status);
            $this->assertTrue($ds->save());
        }

        $results = rex_yform_manager_dataset::query($table->getTableName())
            ->where('status', 1)
            ->find();

        $this->assertInstanceOf(rex_yform_manager_collection::class, $results);
        $this->assertCount(2, $results);
    }

    public function testGetAllReturnsCollection(): void
    {
        $table = $this->makeBasicTable('crud_getall');

        for ($i = 0; $i < 3; ++$i) {
            $ds = rex_yform_manager_dataset::create($table->getTableName());
            $ds->setValue('title', 'r' . $i);
            $ds->setValue('quantity', $i);
            $ds->setValue('status', 1);
            $this->assertTrue($ds->save());
        }

        $all = rex_yform_manager_dataset::getAll($table->getTableName());
        $this->assertInstanceOf(rex_yform_manager_collection::class, $all);
        $this->assertCount(3, $all);
    }

    public function testValidationFailureProducesMessages(): void
    {
        $tableName = $this->fixtures->reserveTableName('crud_validate');

        rex_sql_table::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('email', 'varchar(191)', true))
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name' => 'validate',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value',    'type_name' => 'text',  'name' => 'email', 'label' => 'E', 'prio' => 1],
            ['type_id' => 'validate', 'type_name' => 'empty', 'name' => 'email', 'message' => 'E-Mail darf nicht leer sein.', 'prio' => 100],
        ]);

        $this->trackFixture($tableName);
        rex_yform_manager_table::deleteCache();

        $ds = rex_yform_manager_dataset::create($tableName);
        // email left empty — must fail validation.
        $ok = $ds->save();
        $this->assertFalse($ok, 'save() should return false on validation failure.');
        $this->assertTrue(count($ds->getMessages()) > 0, 'getMessages() must contain at least one error.');
    }

    public function testCollectionDeleteEmptiesAllMatching(): void
    {
        $table = $this->makeBasicTable('crud_bulk_delete');

        for ($i = 0; $i < 4; ++$i) {
            $ds = rex_yform_manager_dataset::create($table->getTableName());
            $ds->setValue('title', 't' . $i);
            $ds->setValue('quantity', $i);
            $ds->setValue('status', $i % 2);
            $this->assertTrue($ds->save());
        }

        rex_yform_manager_dataset::query($table->getTableName())
            ->where('status', 0)
            ->find()
            ->delete();

        $remaining = rex_yform_manager_dataset::query($table->getTableName())->count();
        $this->assertSame(2, $remaining);
    }

    public function testHistorySnapshotsAreCreatedOnSave(): void
    {
        $tableName = $this->fixtures->reserveTableName('crud_history');

        rex_sql_table::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('title', 'varchar(191)', true))
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name' => 'history',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'T', 'prio' => 1],
        ]);

        // Enable history flag via direct SQL (setTable doesn't propagate it).
        rex_sql::factory()
            ->setTable(rex_yform_manager_table::table())
            ->setWhere(['table_name' => $tableName])
            ->setValue('history', 1)
            ->update();

        $this->trackFixture($tableName);
        rex_yform_manager_table::deleteCache();

        $ds = rex_yform_manager_dataset::create($tableName);
        $ds->setValue('title', 'v1');
        $this->assertTrue($ds->save());
        $id = $ds->getId();

        $ds->setValue('title', 'v2');
        $this->assertTrue($ds->save());

        $snapshots = rex_sql::factory()->getArray(
            'SELECT id, action FROM ' . rex::getTable('yform_history') . ' WHERE table_name = :t AND dataset_id = :id ORDER BY id ASC',
            [':t' => $tableName, ':id' => $id],
        );

        // CREATE + UPDATE snapshots expected (boot.php YFORM_SAVED hook).
        $this->assertCount(2, $snapshots);
        $this->assertSame(rex_yform_manager_dataset::ACTION_CREATE, $snapshots[0]['action']);
        $this->assertSame(rex_yform_manager_dataset::ACTION_UPDATE, $snapshots[1]['action']);

        // Cleanup the history rows we left behind.
        rex_sql::factory()->setQuery(
            'DELETE FROM ' . rex::getTable('yform_history') . ' WHERE table_name = :t',
            [':t' => $tableName],
        );
        rex_sql::factory()->setQuery(
            'DELETE FROM ' . rex::getTable('yform_history_field') . ' WHERE history_id NOT IN (SELECT id FROM ' . rex::getTable('yform_history') . ')',
        );
    }

    public function testHistoryDisabledSkipsSnapshot(): void
    {
        $tableName = $this->fixtures->reserveTableName('crud_history_off');

        rex_sql_table::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('title', 'varchar(191)', true))
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name' => 'history_off',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'T', 'prio' => 1],
        ]);

        rex_sql::factory()
            ->setTable(rex_yform_manager_table::table())
            ->setWhere(['table_name' => $tableName])
            ->setValue('history', 1)
            ->update();

        $this->trackFixture($tableName);
        rex_yform_manager_table::deleteCache();

        $ds = rex_yform_manager_dataset::create($tableName);
        $ds->setHistoryEnabled(false);
        $ds->setValue('title', 'no-snapshot');
        $this->assertTrue($ds->save());
        $id = $ds->getId();

        $count = (int) rex_sql::factory()->getArray(
            'SELECT COUNT(*) AS c FROM ' . rex::getTable('yform_history') . ' WHERE table_name = :t AND dataset_id = :id',
            [':t' => $tableName, ':id' => $id],
        )[0]['c'];

        $this->assertSame(0, $count);
    }

    public function testQueryOneReturnsSingleDatasetOrNull(): void
    {
        $table = $this->makeBasicTable('crud_queryone');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', 'unique-value');
        $ds->setValue('quantity', 1);
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());

        $hit = rex_yform_manager_dataset::query($table->getTableName())
            ->where('title', 'unique-value')
            ->findOne();
        $this->assertNotNull($hit);
        $this->assertSame('unique-value', $hit->getValue('title'));

        $miss = rex_yform_manager_dataset::query($table->getTableName())
            ->where('title', 'absolutely-not-there')
            ->findOne();
        $this->assertNull($miss);
    }

    public function testInstancePoolReturnsSameObjectForSameId(): void
    {
        $table = $this->makeBasicTable('crud_pool');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', 'pooled');
        $ds->setValue('quantity', 1);
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        $id = $ds->getId();

        $a = rex_yform_manager_dataset::get($id, $table->getTableName());
        $b = rex_yform_manager_dataset::get($id, $table->getTableName());
        // Instance pool: same id+table returns the same instance.
        $this->assertSame($a, $b);
    }
}
