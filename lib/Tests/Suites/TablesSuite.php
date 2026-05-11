<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Exception;
use Redaxo\YForm\Test\AbstractTestSuite;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform_manager_field;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use Throwable;

use function is_array;
use function is_string;

/**
 * Tests for rex_yform_manager_table_api — the public write API for table
 * definitions. Covers §3.U.3 from .claude/plans/02-test-strategy.md.
 *
 * @package redaxo\yform
 * @internal
 */
final class TablesSuite extends AbstractTestSuite
{
    public function testSetTableCreatesNewRow(): void
    {
        $tableName = $this->fixtures->reserveTableName('create_new');

        // Underlying SQL must exist first.
        rex_sql_table::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name' => 'Created Table',
            'status' => 1,
            'hidden' => 1,
            'prio' => 100,
        ]);

        rex_yform_manager_table::deleteCache();
        $table = rex_yform_manager_table::get($tableName);

        $this->assertInstanceOf(rex_yform_manager_table::class, $table);
        $this->assertSame('Created Table', $table->getName());
        $this->assertTrue($table->isActive());
        $this->assertTrue($table->isHidden());
    }

    public function testSetTableUpdatesExistingRow(): void
    {
        $table = $this->createTestTable('update_existing');

        rex_yform_manager_table_api::setTable([
            'table_name' => $table->getTableName(),
            'name' => 'Renamed',
            'status' => 1,
            'hidden' => 1,
        ]);

        rex_yform_manager_table::deleteCache();
        $reloaded = rex_yform_manager_table::get($table->getTableName());

        $this->assertNotNull($reloaded);
        $this->assertSame('Renamed', $reloaded->getName());
    }

    public function testSetTableWithoutTableNameThrows(): void
    {
        $this->assertThrows(
            Exception::class,
            static fn () => rex_yform_manager_table_api::setTable([]),
            'setTable() ohne table_name muss eine Exception werfen',
        );
    }

    public function testSetTableFieldInsertsNewValueField(): void
    {
        $table = $this->createTestTable('field_insert');

        rex_sql_table::get($table->getTableName())
            ->ensureColumn(new rex_sql_column('title', 'varchar(255)', true))
            ->ensure();

        rex_yform_manager_table_api::setTableField($table->getTableName(), [
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'title',
            'label' => 'Titel',
            'prio' => 10,
        ]);

        rex_yform_manager_table::deleteCache();
        $reloaded = rex_yform_manager_table::require($table->getTableName());
        $field = $reloaded->getValueField('title');

        $this->assertNotNull($field);
        $this->assertSame('title', $field->getName());
        $this->assertSame('text', $field->getTypeName());
    }

    public function testSetTableFieldUpdatesExistingField(): void
    {
        $table = $this->createTestTable('field_update', [
            [
                'type_id' => 'value',
                'type_name' => 'text',
                'name' => 'subject',
                'label' => 'First Label',
                'prio' => 5,
            ],
        ]);

        // Same (type_id, type_name, name) tuple -> must update, not duplicate.
        rex_yform_manager_table_api::setTableField($table->getTableName(), [
            'type_id' => 'value',
            'type_name' => 'text',
            'name' => 'subject',
            'label' => 'Updated Label',
            'prio' => 5,
        ]);

        rex_yform_manager_table::deleteCache();
        $reloaded = rex_yform_manager_table::require($table->getTableName());

        $valueFields = $reloaded->getValueFields();
        $this->assertCount(1, $valueFields, 'setTableField must update in place, not duplicate.');
        $this->assertSame('Updated Label', $reloaded->getValueField('subject')->getLabel());
    }

    public function testTwoValidatorsSameFieldNameAreStoredSeparately(): void
    {
        $table = $this->createTestTable('two_validators', [
            [
                'type_id' => 'value',
                'type_name' => 'text',
                'name' => 'email',
                'label' => 'E-Mail',
                'prio' => 10,
            ],
            [
                'type_id' => 'validate',
                'type_name' => 'empty',
                'name' => 'email',
                'message' => 'Pflicht.',
                'prio' => 100,
            ],
            [
                'type_id' => 'validate',
                'type_name' => 'type',
                'name' => 'email',
                'type_name_internal' => 'email',
                'message' => 'Format.',
                'prio' => 101,
            ],
        ]);

        rex_yform_manager_table::deleteCache();
        $reloaded = rex_yform_manager_table::require($table->getTableName());

        $validators = $reloaded->getFields(['type_id' => 'validate']);
        $this->assertCount(2, $validators, 'Validators with same name but different type_name must coexist.');
    }

    public function testRemoveTableDropsAllFields(): void
    {
        $table = $this->createTestTable('removal', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'a', 'label' => 'A'],
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'b', 'label' => 'B'],
            ['type_id' => 'validate', 'type_name' => 'empty', 'name' => 'a', 'message' => 'pflicht'],
        ]);
        $tableName = $table->getTableName();

        rex_yform_manager_table_api::removeTable($tableName);
        rex_yform_manager_table::deleteCache();

        $this->assertNull(rex_yform_manager_table::get($tableName));

        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT COUNT(*) AS c FROM ' . rex_yform_manager_field::table() . ' WHERE table_name = :t',
            [':t' => $tableName],
        );
        $this->assertSame(0, (int) $rows[0]['c'], 'rex_yform_field rows must be gone after removeTable.');
    }

    public function testExportTablesetsReturnsValidJsonStructure(): void
    {
        $table = $this->createTestTable('export', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'Titel', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'textarea', 'name' => 'body', 'label' => 'Inhalt', 'prio' => 2],
        ]);

        $json = rex_yform_manager_table_api::exportTablesets([$table->getTableName()]);

        $this->assertTrue(is_string($json) && '' !== $json);
        $data = json_decode((string) $json, true);
        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey($table->getTableName(), $data);
        $this->assertArrayHasKey('table', $data[$table->getTableName()]);
        $this->assertArrayHasKey('fields', $data[$table->getTableName()]);
        $this->assertCount(2, $data[$table->getTableName()]['fields']);
    }

    public function testImportTablesetsWithMalformedStructureThrows(): void
    {
        $this->assertThrows(
            Throwable::class,
            static fn () => rex_yform_manager_table_api::importTablesets('[{"no_table_or_fields":1}]'),
        );
    }

    public function testImportTablesetsWithInvalidJsonThrows(): void
    {
        // Plain garbage used to silently no-op (json_decode → null, foreach over null).
        // Now it must throw — same as malformed structure.
        $this->assertThrows(
            Throwable::class,
            static fn () => rex_yform_manager_table_api::importTablesets('not-json-at-all'),
        );
        $this->assertThrows(
            Throwable::class,
            static fn () => rex_yform_manager_table_api::importTablesets('"just-a-string"'),
        );
    }

    public function testRemoveTableCleansUpOrphanFieldRows(): void
    {
        // Issue #1575: rex_yform_manager_table::getFields() silently skips
        // field rows whose type-class isn't autoloadable (e.g. custom field
        // type from an addon currently being uninstalled). removeTable() used
        // to iterate getFields() — so orphan rows survived, and the next
        // install upserted duplicate rows, triggering „More than one field
        // found …" on the third install cycle.
        //
        // The fix: removeTable() now does a bulk DELETE on table_name.
        $table = $this->createTestTable('orphan_cleanup', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'title', 'label' => 'T', 'prio' => 1],
        ]);
        $tableName = $table->getTableName();

        // Inject an orphan field-row with an unknown type — getFields()
        // would ignore it (the field-class can't be instantiated).
        rex_sql::factory()
            ->setTable(rex_yform_manager_field::table())
            ->setValue('table_name', $tableName)
            ->setValue('type_id', 'value')
            ->setValue('type_name', 'nonexistent_custom_type_xyz')
            ->setValue('name', 'ghost_field')
            ->setValue('label', 'Ghost')
            ->setValue('prio', 99)
            ->insert();

        rex_yform_manager_table::deleteCache();

        rex_yform_manager_table_api::removeTable($tableName);

        $remaining = rex_sql::factory()->getArray(
            'SELECT COUNT(*) AS c FROM ' . rex_yform_manager_field::table() . ' WHERE table_name = :t',
            [':t' => $tableName],
        );
        $this->assertSame(0, (int) $remaining[0]['c'], 'removeTable must purge ALL field rows, including orphans.');
    }

    public function testReImportPreservesLocalFieldPrio(): void
    {
        // Issue #1408: re-importing a tableset must not clobber the user's
        // manual reordering of fields. setTableField()'s UPDATE branch now
        // drops 'prio' before applying the tableset values.
        $table = $this->createTestTable('reimport_prio', [
            ['type_id' => 'value', 'type_name' => 'text',    'name' => 'a', 'label' => 'A', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'b', 'label' => 'B', 'prio' => 2],
            ['type_id' => 'value', 'type_name' => 'textarea', 'name' => 'c', 'label' => 'C', 'prio' => 3],
        ]);
        $tableName = $table->getTableName();

        // User manually reorders: a=10, b=5, c=20. (Different from initial 1/2/3.)
        $sql = rex_sql::factory();
        $sql->setQuery(
            'UPDATE ' . rex_yform_manager_field::table() . ' SET prio = CASE name WHEN :a THEN 10 WHEN :b THEN 5 WHEN :c THEN 20 END WHERE table_name = :t',
            [':a' => 'a', ':b' => 'b', ':c' => 'c', ':t' => $tableName],
        );
        rex_yform_manager_table::deleteCache();

        // Re-import the original tableset (with original prios 1/2/3).
        $json = (string) rex_yform_manager_table_api::exportTablesets([$tableName]);
        rex_yform_manager_table_api::importTablesets($json);
        rex_yform_manager_table::deleteCache();

        $imported = rex_yform_manager_table::require($tableName);
        $byName = [];
        foreach ($imported->getValueFields() as $f) {
            $byName[$f->getName()] = (int) $f->getElement('prio');
        }
        $this->assertSame(10, $byName['a'] ?? null, 'a-prio must stay at user-set 10');
        $this->assertSame(5, $byName['b'] ?? null, 'b-prio must stay at user-set 5');
        $this->assertSame(20, $byName['c'] ?? null, 'c-prio must stay at user-set 20');
    }

    public function testExportImportRoundtripPreservesFields(): void
    {
        $table = $this->createTestTable('roundtrip', [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'name', 'label' => 'Name', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'age', 'label' => 'Alter', 'prio' => 2],
            ['type_id' => 'validate', 'type_name' => 'empty', 'name' => 'name', 'message' => 'Pflicht.'],
        ]);
        $tableName = $table->getTableName();

        $json = rex_yform_manager_table_api::exportTablesets([$tableName]);
        $this->assertNotSame('', (string) $json);

        // Roundtrip under the SAME name: remove, then re-import.
        rex_yform_manager_table_api::removeTable($tableName);
        rex_yform_manager_table::deleteCache();

        rex_yform_manager_table_api::importTablesets((string) $json);
        rex_yform_manager_table::deleteCache();

        $imported = rex_yform_manager_table::require($tableName);
        $this->assertCount(2, $imported->getValueFields(), 'Value fields must survive export/import roundtrip.');
        $this->assertCount(1, $imported->getFields(['type_id' => 'validate']), 'Validators must survive too.');
    }

    public function testImportTablesetsUnderRenamedTablePreservesFields(): void
    {
        // Regression: setTableField() used to overwrite the explicit $table_name
        // arg via the foreach loop, so importing an export under a new name lost
        // every field. setTableField() now drops table_name from the field row.
        $source = $this->createTestTable('rt_source', [
            ['type_id' => 'value', 'type_name' => 'text',    'name' => 'name', 'label' => 'Name', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'age',  'label' => 'Alter', 'prio' => 2],
            ['type_id' => 'validate', 'type_name' => 'empty', 'name' => 'name', 'message' => 'Pflicht.'],
        ]);
        $sourceName = $source->getTableName();

        $json = (string) rex_yform_manager_table_api::exportTablesets([$sourceName]);
        $this->assertNotSame('', $json);

        $renamed = $this->fixtures->reserveTableName('rt_target');
        // Rewrite the top-level table_name key in the JSON to the new name.
        $decoded = json_decode($json, true);
        $entry = $decoded[$sourceName];
        $entry['table']['table_name'] = $renamed;
        $rewritten = json_encode([$renamed => $entry]);

        rex_yform_manager_table_api::importTablesets((string) $rewritten);
        rex_yform_manager_table::deleteCache();

        $imported = rex_yform_manager_table::require($renamed);
        $this->assertCount(2, $imported->getValueFields(), 'Value fields must survive renamed import.');
        $this->assertCount(1, $imported->getFields(['type_id' => 'validate']), 'Validators too.');
    }
}
