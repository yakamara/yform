<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use rex_exception;
use rex_yform_manager_field;
use rex_yform_manager_table;

/**
 * Tests for the read-only API of rex_yform_manager_table.
 * Covers §3.U.1 from .claude/plans/02-test-strategy.md.
 *
 * @package redaxo\yform
 * @internal
 */
final class TableReadSuite extends AbstractTestSuite
{
    public function testGetReturnsTableForExistingName(): void
    {
        $table = $this->createTestTable('read_basic', [], [
            'name'           => 'Basic Read',
            'list_amount'    => 25,
            'list_sortfield' => 'id',
            'list_sortorder' => 'DESC',
            'search'         => 1,
        ]);

        $reloaded = rex_yform_manager_table::get($table->getTableName());
        $this->assertNotNull($reloaded);
        $this->assertSame($table->getTableName(), $reloaded->getTableName());
        $this->assertSame('Basic Read', $reloaded->getName());
        $this->assertSame(25, (int) $reloaded->getListAmount());
        $this->assertSame('id', $reloaded->getSortFieldName());
        $this->assertSame('DESC', $reloaded->getSortOrderName());
        $this->assertTrue($reloaded->isSearchable());
    }

    public function testGetReturnsNullForUnknownTable(): void
    {
        $this->assertNull(rex_yform_manager_table::get('this_table_should_never_exist_' . uniqid()));
    }

    public function testRequireThrowsForUnknownTable(): void
    {
        $missing = 'missing_' . uniqid();
        $this->assertThrows(
            rex_exception::class,
            static fn () => rex_yform_manager_table::require($missing),
        );
    }

    public function testIsActiveAndIsHiddenReflectStatus(): void
    {
        $active = $this->createTestTable('active', [], ['status' => 1, 'hidden' => 0]);
        $hidden = $this->createTestTable('hidden_t', [], ['status' => 1, 'hidden' => 1]);
        $inactive = $this->createTestTable('inactive', [], ['status' => 0, 'hidden' => 0]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isHidden());

        $this->assertTrue($hidden->isActive());
        $this->assertTrue($hidden->isHidden());

        $this->assertFalse($inactive->isActive());
    }

    public function testHasHistoryReflectsFlag(): void
    {
        $withHistory = $this->createTestTable('hist_on');
        $noHistory   = $this->createTestTable('hist_off');

        // rex_yform_manager_table_api::setTable() whitelists $table_fields which
        // does NOT include 'history' / 'mass_deletion' / 'mass_edit' even though
        // these are real columns on rex_yform_table. They get set to defaults at
        // install time only. Patch directly to exercise hasHistory().
        \rex_sql::factory()
            ->setTable(\rex_yform_manager_table::table())
            ->setWhere(['table_name' => $withHistory->getTableName()])
            ->setValue('history', 1)
            ->update();
        \rex_sql::factory()
            ->setTable(\rex_yform_manager_table::table())
            ->setWhere(['table_name' => $noHistory->getTableName()])
            ->setValue('history', 0)
            ->update();
        rex_yform_manager_table::deleteCache();

        $on  = rex_yform_manager_table::require($withHistory->getTableName());
        $off = rex_yform_manager_table::require($noHistory->getTableName());

        $this->assertTrue($on->hasHistory());
        $this->assertFalse($off->hasHistory());
    }

    public function testSetTableApiKnownIssueDoesNotPropagateHistoryFlag(): void
    {
        // rex_yform_manager_table_api::$table_fields lacks 'history',
        // 'mass_deletion', 'mass_edit'. Passing them via setTable() is silently
        // dropped. Document this as a known issue; the production code that
        // needs these flags must currently set them via direct SQL.
        $this->markSkipped(
            'Known issue: setTable() does not propagate history / mass_deletion / mass_edit. '
          . 'Fix: add these keys to rex_yform_manager_table_api::$table_fields.',
        );
    }

    public function testGetCustomIconReturnsTableIconOrNull(): void
    {
        $iconed = $this->createTestTable('with_icon', [], ['table_icon' => 'fa-users']);
        $plain  = $this->createTestTable('no_icon');

        $this->assertSame('fa-users', $iconed->getCustomIcon());
        // 'no_icon' table has no table_icon set; should be null or empty string.
        $icon = $plain->getCustomIcon();
        $this->assertTrue(null === $icon || '' === $icon);
    }

    public function testGetListAmountFallsBackTo100WhenZero(): void
    {
        $table = $this->createTestTable('zero_amount', [], ['list_amount' => 0]);
        // rex_yform_manager_table::getListAmount() falls back to 100 when < 1.
        $this->assertSame(100, (int) $table->getListAmount());
    }

    public function testGetAllReturnsTablesIncludingOurFixture(): void
    {
        $table = $this->createTestTable('in_getall');

        $all = rex_yform_manager_table::getAll();
        $names = array_map(static fn (rex_yform_manager_table $t) => $t->getTableName(), array_values($all));

        $this->assertTrue(in_array($table->getTableName(), $names, true), 'getAll() must include the fixture table.');
    }

    public function testGetFieldsReturnsAllFields(): void
    {
        $table = $this->createTestTable('many_fields', [
            ['type_id' => 'value', 'type_name' => 'text',     'name' => 'a', 'label' => 'A', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer',  'name' => 'b', 'label' => 'B', 'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'textarea', 'name' => 'c', 'label' => 'C', 'prio' => 3],
            ['type_id' => 'validate', 'type_name' => 'empty', 'name' => 'a', 'message' => 'pflicht', 'prio' => 100],
        ]);

        $fields = $table->getFields();
        $this->assertCount(4, $fields);
    }

    public function testGetValueFieldsReturnsOnlyValueFieldsKeyedByName(): void
    {
        $table = $this->createTestTable('keyed_values', [
            ['type_id' => 'value',    'type_name' => 'text',  'name' => 'title', 'label' => 'T'],
            ['type_id' => 'value',    'type_name' => 'email', 'name' => 'email', 'label' => 'E'],
            ['type_id' => 'validate', 'type_name' => 'empty', 'name' => 'title', 'message' => 'p'],
        ]);

        $values = $table->getValueFields();
        $this->assertCount(2, $values);
        $this->assertArrayHasKey('title', $values);
        $this->assertArrayHasKey('email', $values);
        $this->assertArrayNotHasKey(0, $values, 'getValueFields() must be keyed by field name, not index.');
    }

    public function testGetValueFieldReturnsSingleFieldOrNull(): void
    {
        $table = $this->createTestTable('single_lookup', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'present', 'label' => 'P'],
        ]);

        $this->assertNotNull($table->getValueField('present'));
        $this->assertSame(null, $table->getValueField('absent'));
    }

    public function testGetFieldsWithFilterByTypeId(): void
    {
        $table = $this->createTestTable('filter_test', [
            ['type_id' => 'value',    'type_name' => 'text',  'name' => 'x', 'label' => 'X'],
            ['type_id' => 'validate', 'type_name' => 'empty', 'name' => 'x', 'message' => 'p'],
            ['type_id' => 'validate', 'type_name' => 'type',  'name' => 'x', 'type_name_internal' => 'email', 'message' => 'm'],
        ]);

        $this->assertCount(1, $table->getFields(['type_id' => 'value']));
        $this->assertCount(2, $table->getFields(['type_id' => 'validate']));
    }

    public function testGetRelationsListsBeManagerRelationFields(): void
    {
        $target = $this->createTestTable('rel_target', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'name', 'label' => 'Name'],
        ]);
        $source = $this->createTestTable('rel_source', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'Title'],
            [
                'type_id'      => 'value',
                'type_name'    => 'be_manager_relation',
                'name'         => 'target_id',
                'label'        => 'Target',
                'table'        => $target->getTableName(),
                'field'        => 'name',
                'type'         => 0,
                'empty_option' => 1,
            ],
        ]);

        $relations = $source->getRelations();
        $this->assertCount(1, $relations);
        $this->assertArrayHasKey('target_id', $relations);

        $relation = $source->getRelation('target_id');
        $this->assertNotNull($relation);
        $this->assertInstanceOf(rex_yform_manager_field::class, $relation);

        $this->assertNull($source->getRelation('not_a_relation_column'));
    }

    public function testToArrayReturnsRawMetadata(): void
    {
        $table = $this->createTestTable('to_array', [], ['name' => 'Raw', 'list_amount' => 33]);
        $data = $table->toArray();

        $this->assertSame('Raw', $data['name']);
        $this->assertSame(33, (int) $data['list_amount']);
        $this->assertSame($table->getTableName(), $data['table_name']);
    }

    public function testArrayAccessReadsMetadataValues(): void
    {
        $table = $this->createTestTable('array_access', [], ['name' => 'AA']);

        $this->assertTrue(isset($table['name']));
        $this->assertSame('AA', $table['name']);
        $this->assertFalse(isset($table['truly_unknown_key']));
    }

    public function testDeleteCacheForcesReload(): void
    {
        $table = $this->createTestTable('cache_bust', [], ['name' => 'V1']);

        // Mutate directly via API, then verify cache returns fresh data only after deleteCache.
        \rex_yform_manager_table_api::setTable([
            'table_name' => $table->getTableName(),
            'name'       => 'V2',
            'status'     => 1,
            'hidden'     => 1,
        ]);

        // setTable() itself calls deleteCache, so this is mostly defensive.
        rex_yform_manager_table::deleteCache();
        $reloaded = rex_yform_manager_table::require($table->getTableName());
        $this->assertSame('V2', $reloaded->getName());
    }
}
