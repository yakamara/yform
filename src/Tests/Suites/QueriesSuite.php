<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use Redaxo\Core\Util\Pager;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Manager\Collection;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Query;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;

use function array_slice;
use function count;
use function in_array;
use function is_int;

/**
 * Tests for Query (fluent SQL builder).
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
            Api::removeTable($this->tableName);
        } catch (Throwable) {
        }
        try {
            DbTable::get($this->tableName)->drop();
        } catch (Throwable) {
        }

        DbTable::get($this->tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('name', 'varchar(191)', true))
            ->ensureColumn(new Column('age', 'int(11)', true))
            ->ensureColumn(new Column('status', 'int(11)', true))
            ->ensureColumn(new Column('tags', 'varchar(191)', true))
            ->ensureColumn(new Column('archived_at', 'datetime', true))
            ->ensure();

        Api::setTable([
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
        Table::deleteCache();
    }

    public function setUp(): void
    {
        // Reset row state — setUp runs per-method on the same table.
        Sql::factory()->setQuery('TRUNCATE `' . $this->tableName . '`');

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
            $sql = Sql::factory();
            $sql->setTable($this->tableName);
            $sql->setValue('name', $name);
            $sql->setValue('age', $age);
            $sql->setValue('status', $status);
            $sql->setValue('tags', $tags);
            // setValue with null doesn't reliably persist NULL with Sql in some
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

    private function q(): Query
    {
        return Dataset::query($this->tableName);
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
            ->whereNested(static function (Query $sub): void {
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

        $byId = Dataset::query($this->tableName)->findId($aliceId);
        $this->assertNotNull($byId);
        $this->assertSame('Alice', $byId->getValue('name'));
        $this->assertNull(Dataset::query($this->tableName)->findId(987654321));
    }

    public function testCollectionGetIdsReturnsIntList(): void
    {
        // Note: Query::findIds(array $ids) is "WHERE id IN (...)"
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
        $pager = new Pager(2);
        $page1 = $this->q()->orderBy('age')->paginate($pager);
        $this->assertInstanceOf(Collection::class, $page1);
        $this->assertCount(2, $page1);
        $this->assertSame(6, $pager->getRowCount());
        // Pager::getLastPage() is 0-indexed. 6 rows / page size 2 -> pages 0,1,2 -> last = 2.
        $this->assertSame(2, $pager->getLastPage());
        $this->assertSame(3, $pager->getPageCount());
    }

    public function testFindReturnsCollectionAndIsIterable(): void
    {
        $collection = $this->q()->orderBy('age')->find();
        $this->assertInstanceOf(Collection::class, $collection);

        $names = [];
        foreach ($collection as $row) {
            $names[] = $row->getValue('name');
        }
        $this->assertSame(['Alice', 'Eve', 'Bob', 'Charlie', 'Diana', 'Frank'], $names);
    }

    public function testGroupByWithHaving(): void
    {
        // Test data sanity check.
        $sanity = Sql::factory()->getArray(
            'SELECT status, COUNT(*) c FROM `' . $this->tableName . '` GROUP BY status HAVING c >= 2 ORDER BY status',
        );
        $this->assertSame(2, count($sanity), 'Expecting status=0 and status=1 to each have >= 2 rows.');

        // Build the same query via the Query builder and execute
        // it directly. (Don't use ->count() here: it appends SELECT COUNT(*) AS count
        // and overrides the alias the HAVING clause refers to.)
        $query = $this->q()
            ->resetSelect()
            ->selectRaw('`' . $this->tableName . '`.`status`')
            ->selectRaw('COUNT(*)', 'c')
            ->groupBy('status')
            ->havingRaw('c >= :min', ['min' => 2])
            ->resetOrderBy();

        $rows = Sql::factory()->getArray($query->getQuery(), $query->getParams());
        $this->assertSame(2, count($rows));
    }
}
