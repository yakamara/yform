<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Exception;
use Redaxo\YForm\Test\AbstractTestSuite;
use rex_sql;
use rex_yform_manager_field;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

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
        \rex_sql_table::get($tableName)
            ->ensurePrimaryIdColumn()
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name'       => 'Created Table',
            'status'     => 1,
            'hidden'     => 1,
            'prio'       => 100,
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
            'name'       => 'Renamed',
            'status'     => 1,
            'hidden'     => 1,
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

        \rex_sql_table::get($table->getTableName())
            ->ensureColumn(new \rex_sql_column('title', 'varchar(255)', true))
            ->ensure();

        rex_yform_manager_table_api::setTableField($table->getTableName(), [
            'type_id'    => 'value',
            'type_name'  => 'text',
            'name'       => 'title',
            'label'      => 'Titel',
            'prio'       => 10,
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
                'type_id'   => 'value',
                'type_name' => 'text',
                'name'      => 'subject',
                'label'     => 'First Label',
                'prio'      => 5,
            ],
        ]);

        // Same (type_id, type_name, name) tuple -> must update, not duplicate.
        rex_yform_manager_table_api::setTableField($table->getTableName(), [
            'type_id'   => 'value',
            'type_name' => 'text',
            'name'      => 'subject',
            'label'     => 'Updated Label',
            'prio'      => 5,
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
                'type_id'   => 'value',
                'type_name' => 'text',
                'name'      => 'email',
                'label'     => 'E-Mail',
                'prio'      => 10,
            ],
            [
                'type_id'   => 'validate',
                'type_name' => 'empty',
                'name'      => 'email',
                'message'   => 'Pflicht.',
                'prio'      => 100,
            ],
            [
                'type_id'           => 'validate',
                'type_name'         => 'type',
                'name'              => 'email',
                'type_name_internal' => 'email',
                'message'           => 'Format.',
                'prio'              => 101,
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
        // Note: importTablesets() does NOT validate the JSON string itself —
        // pure garbage like 'not-json-at-all' is silently no-op'd (warning only).
        // The actual validation path checks that every entry has table+fields keys.
        $this->assertThrows(
            \Throwable::class,
            static fn () => rex_yform_manager_table_api::importTablesets('[{"no_table_or_fields":1}]'),
        );
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

    public function testImportTablesetsKnownIssueRenameLosesFields(): void
    {
        // Known issue uncovered while writing this suite:
        // rex_yform_manager_table_api::setTableField()'s INSERT branch first
        // calls $sql->setValue('table_name', $table_name) with the explicit
        // argument, then unconditionally loops `foreach ($table_field as $k => $v)`
        // and overwrites every value including 'table_name'. Since exportTablesets()
        // embeds the original 'table_name' inside each field row, importing under
        // a different name causes the field rows to be inserted with the OLD
        // table_name. The renamed table ends up with zero fields.
        //
        // Fix-Vorschlag: in setTableField() vor dem foreach
        // `unset($table_field['table_name']);` aufrufen, ODER in importTablesets()
        // die field-table_name explizit auf den neuen Namen umsetzen.
        //
        // Until that gets fixed, this test stays skipped to document the gap.
        $this->markSkipped('Known issue: importTablesets() under a different name drops fields. See test comment.');
    }
}
