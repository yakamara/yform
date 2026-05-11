<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use ReflectionClass;
use rex_pager;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform_manager_collection;
use rex_yform_manager_dataset;
use rex_yform_manager_query;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use Throwable;

use function array_slice;
use function count;
use function in_array;
use function is_int;

/**
 * Tests for rex_yform_manager_query (fluent SQL builder).
 * Covers §3.I.2 from .claude/plans/02-test-strategy.md.
 *
 * Uses a single fixture table populated once per test (setUp) with 6 rows
 * spanning the value ranges the tests check.
 *
 * @package redaxo\yform
 * @internal
 */
final class QueriesSuite extends AbstractTestSuite
{
    private string $tableName = '';

    public function setUpBeforeClass(): void
    {
        $this->tableName = $this->fixtures->reserveTableName('q_main');

        // Force-drop any leftover from earlier crashed runs so the schema is
        // fresh and `archived_at` is guaranteed nullable.
        try {
            rex_yform_manager_table_api::removeTable($this->tableName);
        } catch (Throwable) {
        }
        try {
            rex_sql_table::get($this->tableName)->drop();
        } catch (Throwable) {
        }

        rex_sql_table::get($this->tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('name', 'varchar(191)', true))
            ->ensureColumn(new rex_sql_column('age', 'int(11)', true))
            ->ensureColumn(new rex_sql_column('status', 'int(11)', true))
            ->ensureColumn(new rex_sql_column('tags', 'varchar(191)', true))
            ->ensureColumn(new rex_sql_column('archived_at', 'datetime', true))
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $this->tableName,
            'name' => 'qmain',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text',    'name' => 'name',        'label' => 'Name',   'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'age',         'label' => 'Age',    'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'status',      'label' => 'Status', 'prio' => 3],
            ['type_id' => 'value', 'type_name' => 'text',    'name' => 'tags',        'label' => 'Tags',   'prio' => 4],
            ['type_id' => 'value', 'type_name' => 'text',    'name' => 'archived_at', 'label' => 'Arch',   'prio' => 5],
        ]);
        $this->trackFixture($this->tableName);
        rex_yform_manager_table::deleteCache();
    }

    public function setUp(): void
    {
        // Reset row state — setUp runs per-method on the same table.
        rex_sql::factory()->setQuery('TRUNCATE `' . $this->tableName . '`');

        // Seed 6 rows with diverse data.
        $rows = [
            ['Alice',   20, 1, '1,2,3',   null],
            ['Bob',     30, 1, '2,4',     null],
            ['Charlie', 40, 0, '1,5',     '2024-01-01 00:00:00'],
            ['Diana',   50, 1, '3',       null],
            ['Eve',     25, 2, '1,3,5',   null],
            ['Frank',   60, 0, '',        '2025-02-02 12:00:00'],
        ];
        foreach ($rows as [$name, $age, $status, $tags, $archived]) {
            $sql = rex_sql::factory();
            $sql->setTable($this->tableName);
            $sql->setValue('name', $name);
            $sql->setValue('age', $age);
            $sql->setValue('status', $status);
            $sql->setValue('tags', $tags);
            // setValue with null doesn't reliably persist NULL with rex_sql in some
            // configurations — drop to raw SQL for explicit NULL.
            if (null === $archived) {
                $sql->setRawValue('archived_at', 'NULL');
            } else {
                $sql->setValue('archived_at', $archived);
            }
            $sql->insert();
        }
    }

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

    private function q(): rex_yform_manager_query
    {
        return rex_yform_manager_dataset::query($this->tableName);
    }

    public function testWhereEqualFiltersByValue(): void
    {
        $this->assertSame(3, $this->q()->where('status', 1)->count());
    }

    public function testWhereWithOperatorGreaterEqual(): void
    {
        $this->assertSame(3, $this->q()->where('age', 40, '>=')->count());
    }

    public function testWhereWithArrayValueGeneratesInClause(): void
    {
        // status=1: Alice, Bob, Diana (3) ; status=2: Eve (1) -> total 4
        $this->assertSame(4, $this->q()->where('status', [1, 2])->count());
    }

    public function testWhereNotInvertsMatch(): void
    {
        // total 6, status=1 is 3 -> whereNot returns 3
        $this->assertSame(3, $this->q()->whereNot('status', 1)->count());
    }

    public function testWhereNullAndNotNull(): void
    {
        $this->assertSame(4, $this->q()->whereNull('archived_at')->count());
        $this->assertSame(2, $this->q()->whereNotNull('archived_at')->count());
    }

    public function testWhereBetween(): void
    {
        // age 25..40 inclusive: Eve, Bob, Charlie -> 3
        $this->assertSame(3, $this->q()->whereBetween('age', 25, 40)->count());
    }

    public function testWhereRawWithNamedParam(): void
    {
        $hits = $this->q()
            ->whereRaw('LOWER(name) = LOWER(:n)', ['n' => 'alice'])
            ->find();
        $this->assertCount(1, $hits);
        $this->assertSame('Alice', $hits[0]->getValue('name'));
    }

    public function testWhereListContainsSingleValue(): void
    {
        // tag "3" appears in: Alice (1,2,3), Diana (3), Eve (1,3,5) → 3
        $this->assertSame(3, $this->q()->whereListContains('tags', 3)->count());
    }

    public function testWhereListContainsMultipleValues(): void
    {
        // tag "4" OR "5": Bob(4), Charlie(5), Eve(5) → 3
        $this->assertSame(3, $this->q()->whereListContains('tags', [4, 5])->count());
    }

    public function testOrOperatorOnTopLevel(): void
    {
        $hits = $this->q()
            ->setWhereOperator('OR')
            ->where('age', 20)
            ->where('age', 60)
            ->find();
        $this->assertCount(2, $hits);
    }

    public function testWhereNestedArrayFormUsesGroupedOr(): void
    {
        $hits = $this->q()
            ->whereNested(['age' => 20, 'name' => 'Frank'], 'OR')
            ->find();
        // Alice (age=20) + Frank → 2
        $this->assertCount(2, $hits);
    }

    public function testWhereNestedCallbackUsesGroupedOr(): void
    {
        $hits = $this->q()
            ->where('status', 1)
            ->whereNested(static function (rex_yform_manager_query $sub): void {
                $sub->where('age', 20)->where('age', 50);
            }, 'OR')
            ->find();
        // status=1 AND (age=20 OR age=50): Alice + Diana → 2
        $this->assertCount(2, $hits);
    }

    public function testOrderByAscAndDesc(): void
    {
        $asc = $this->q()->orderBy('age', 'ASC')->find();
        $desc = $this->q()->orderBy('age', 'DESC')->find();
        $this->assertSame('Alice', $asc[0]->getValue('name'));
        $this->assertSame('Frank', $desc[0]->getValue('name'));
    }

    public function testLimit(): void
    {
        $this->assertCount(2, $this->q()->orderBy('age')->limit(2)->find());
        // offset 2, count 2 → 2 rows
        $this->assertCount(2, $this->q()->orderBy('age')->limit(2, 2)->find());
    }

    public function testCountAndExists(): void
    {
        $this->assertSame(6, $this->q()->count());
        $this->assertTrue($this->q()->where('name', 'Alice')->exists());
        $this->assertFalse($this->q()->where('name', 'not-real')->exists());
    }

    public function testFindOneAndFindId(): void
    {
        $alice = $this->q()->where('name', 'Alice')->findOne();
        $this->assertNotNull($alice);
        $aliceId = $alice->getId();

        $byId = rex_yform_manager_dataset::query($this->tableName)->findId($aliceId);
        $this->assertNotNull($byId);
        $this->assertSame('Alice', $byId->getValue('name'));
        $this->assertNull(rex_yform_manager_dataset::query($this->tableName)->findId(987654321));
    }

    public function testCollectionGetIdsReturnsIntList(): void
    {
        // Note: rex_yform_manager_query::findIds(array $ids) is "WHERE id IN (...)"
        // — NOT a getter for the result ids. To get the ids of a result set, call
        // find()->getIds() on the collection.
        $ids = $this->q()->where('status', 1)->find()->getIds();
        $this->assertCount(3, $ids);
        foreach ($ids as $id) {
            $this->assertTrue(is_int($id) && $id > 0);
        }
    }

    public function testFindIdsFiltersByIdList(): void
    {
        $allIds = $this->q()->find()->getIds();
        $subset = array_slice($allIds, 0, 2);
        $hits = $this->q()->findIds($subset);
        $this->assertCount(2, $hits);
    }

    public function testPaginateReturnsPageWindowAndPager(): void
    {
        $pager = new rex_pager(2);
        $page1 = $this->q()->orderBy('age')->paginate($pager);
        $this->assertInstanceOf(rex_yform_manager_collection::class, $page1);
        $this->assertCount(2, $page1);
        $this->assertSame(6, $pager->getRowCount());
        // rex_pager::getLastPage() is 0-indexed. 6 rows / page size 2 -> pages 0,1,2 -> last = 2.
        $this->assertSame(2, $pager->getLastPage());
        $this->assertSame(3, $pager->getPageCount());
    }

    public function testFindReturnsCollectionAndIsIterable(): void
    {
        $collection = $this->q()->orderBy('age')->find();
        $this->assertInstanceOf(rex_yform_manager_collection::class, $collection);

        $names = [];
        foreach ($collection as $row) {
            $names[] = $row->getValue('name');
        }
        $this->assertSame(['Alice', 'Eve', 'Bob', 'Charlie', 'Diana', 'Frank'], $names);
    }

    public function testGroupByWithHaving(): void
    {
        // Test data sanity check.
        $sanity = rex_sql::factory()->getArray(
            'SELECT status, COUNT(*) c FROM `' . $this->tableName . '` GROUP BY status HAVING c >= 2 ORDER BY status',
        );
        $this->assertSame(2, count($sanity), 'Expecting status=0 and status=1 to each have >= 2 rows.');

        // Build the same query via the rex_yform_manager_query builder and execute
        // it directly. (Don't use ->count() here: it appends SELECT COUNT(*) AS count
        // and overrides the alias the HAVING clause refers to.)
        $query = $this->q()
            ->resetSelect()
            ->selectRaw('`' . $this->tableName . '`.`status`')
            ->selectRaw('COUNT(*)', 'c')
            ->groupBy('status')
            ->havingRaw('c >= ?', [2])
            ->resetOrderBy();

        $rows = rex_sql::factory()->getArray($query->getQuery(), $query->getParams());
        $this->assertSame(2, count($rows));
    }
}
