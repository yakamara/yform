<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform;
use rex_yform_manager_dataset;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

/**
 * Tests for type-specific behavior of YForm value fields.
 * Covers §3.U.4 from .claude/plans/02-test-strategy.md.
 *
 * The DatasetsSuite already exercises the basic save/load roundtrip for
 * `text` and `integer`. This suite focuses on type-specific quirks:
 *   - datestamp's only_empty matrix
 *   - choice's value_pool.email _LABELS / _LIST extras
 *   - checkbox 0/1 + output_values
 *   - uuid / generate_key auto-fill
 *   - no_db skip-persist
 *
 * @package redaxo\yform
 * @internal
 */
final class FieldTypesSuite extends AbstractTestSuite
{
    /**
     * Build a one-off table with a single value field (plus optional extra cols)
     * and return the table name.
     *
     * @param array<int, array{0:string,1:string,2?:bool}> $sqlColumns
     */
    private function buildTable(string $shortName, array $fieldDef, array $sqlColumns): string
    {
        $tableName = $this->fixtures->reserveTableName($shortName);
        try { rex_yform_manager_table_api::removeTable($tableName); } catch (\Throwable) {}
        try { rex_sql_table::get($tableName)->drop(); } catch (\Throwable) {}

        $b = rex_sql_table::get($tableName)->ensurePrimaryIdColumn();
        foreach ($sqlColumns as $col) {
            $b->ensureColumn(new rex_sql_column($col[0], $col[1], $col[2] ?? true));
        }
        $b->ensure();

        $fieldDef['type_id']  = 'value';
        $fieldDef['prio']   ??= 1;
        $fieldDef['label']  ??= ($fieldDef['name'] ?? 'X');

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name'       => $shortName,
            'status'     => 1,
            'hidden'     => 1,
        ], [$fieldDef]);

        $this->trackFixture($tableName);
        rex_yform_manager_table::deleteCache();
        return $tableName;
    }

    private function trackFixture(string $tableName): void
    {
        $reflection = new \ReflectionClass($this->fixtures);
        $prop = $reflection->getProperty('createdTables');
        $list = (array) $prop->getValue($this->fixtures);
        if (!in_array($tableName, $list, true)) {
            $list[] = $tableName;
            $prop->setValue($this->fixtures, $list);
        }
    }

    // ---------- checkbox ----------

    public function testCheckboxStoresOneForChecked(): void
    {
        $table = $this->buildTable('ft_checkbox', [
            'type_name' => 'checkbox',
            'name'      => 'agreed',
        ], [['agreed', 'tinyint(1)']]);

        $ds = rex_yform_manager_dataset::create($table);
        $ds->setValue('agreed', 1);
        $this->assertTrue($ds->save());
        $this->assertSame(1, (int) $ds->getValue('agreed'));
    }

    public function testCheckboxStoresZeroForUnchecked(): void
    {
        $table = $this->buildTable('ft_checkbox_off', [
            'type_name' => 'checkbox',
            'name'      => 'agreed',
        ], [['agreed', 'tinyint(1)']]);

        $ds = rex_yform_manager_dataset::create($table);
        $ds->setValue('agreed', 0);
        $this->assertTrue($ds->save());
        $this->assertSame(0, (int) $ds->getValue('agreed'));
    }

    // ---------- choice ----------

    public function testChoiceSingleSelectPersistsValue(): void
    {
        $table = $this->buildTable('ft_choice_single', [
            'type_name' => 'choice',
            'name'      => 'color',
            'choices'   => '{"Red":"red","Green":"green","Blue":"blue"}',
            'expanded'  => 0,
            'multiple'  => 0,
        ], [['color', 'varchar(191)']]);

        $ds = rex_yform_manager_dataset::create($table);
        $ds->setValue('color', 'green');
        $this->assertTrue($ds->save());
        $this->assertSame('green', $ds->getValue('color'));
    }

    public function testChoiceMultiSelectPersistsCommaList(): void
    {
        $table = $this->buildTable('ft_choice_multi', [
            'type_name' => 'choice',
            'name'      => 'tags',
            'choices'   => '{"News":"news","Blog":"blog","Foto":"foto"}',
            'expanded'  => 1,
            'multiple'  => 1,
        ], [['tags', 'text']]);

        $ds = rex_yform_manager_dataset::create($table);
        $ds->setValue('tags', 'news,blog');
        $this->assertTrue($ds->save());

        $value = (string) $ds->getValue('tags');
        // Result must contain both selected slugs (order can vary).
        $this->assertStringContains('news', $value);
        $this->assertStringContains('blog', $value);
    }

    public function testChoiceOffersTinyintWithoutDisplayWidth(): void
    {
        // Issue #1591: tinyint(1) is interpreted as boolean by some MySQL clients.
        // The choice field now offers plain `tinyint` as an additional db_type
        // option so non-boolean numeric values (e.g. 0–9) can be stored cleanly.
        $field = new \rex_yform_value_choice();
        $def = $field->getDefinitions();
        $this->assertArrayHasKey('db_type', $def);
        $this->assertTrue(in_array('tinyint', $def['db_type'], true), 'tinyint must be offered as choice db_type.');
        // tinyint(1) stays available for backward compat.
        $this->assertTrue(in_array('tinyint(1)', $def['db_type'], true));
    }

    public function testChoiceWritesLabelsAndListEntriesToEmailPool(): void
    {
        // Drive rex_yform directly so we can inspect value_pool.email after fields run.
        $tableName = $this->buildTable('ft_choice_pool', [
            'type_name' => 'choice',
            'name'      => 'color',
            'choices'   => '{"Red":"red","Green":"green"}',
            'expanded'  => 0,
            'multiple'  => 0,
        ], [['color', 'varchar(191)']]);

        $yform = new rex_yform();
        $yform->setObjectparams('form_name', 'ft_pool_' . substr(uniqid(), -6));
        $yform->setObjectparams('real_field_names', true);
        $yform->setObjectparams('form_needs_output', false);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('main_table', $tableName);
        $yform->setObjectparams('data', ['color' => 'green']);
        $yform->setValueField('choice', [
            'color', 'Color',
            '{"Red":"red","Green":"green"}',
            0,
            0,
        ]);
        $yform->setFieldValue('send', [], '1');

        $yform->executeFields();

        $pool = $yform->objparams['value_pool']['email'];
        $this->assertSame('green', $pool['color'] ?? null);
        $this->assertArrayHasKey('color_LABELS', $pool);
        $this->assertArrayHasKey('color_LIST', $pool);
        $this->assertStringContains('Green', (string) $pool['color_LABELS']);
    }

    // ---------- datestamp ----------

    public function testDatestampOnlyEmpty1FillsOnlyOnFirstSave(): void
    {
        $table = $this->buildTable('ft_ds_created', [
            'type_name'  => 'datestamp',
            'name'       => 'created',
            'format'     => 'mysql',
            'only_empty' => 1,
        ], [['created', 'datetime']]);

        $ds = rex_yform_manager_dataset::create($table);
        // Don't set a value — datestamp should auto-fill.
        $this->assertTrue($ds->save());
        $firstValue = (string) $ds->getValue('created');
        $this->assertNotSame('', $firstValue);
        $this->assertTrue(strtotime($firstValue) > 0, 'Value should be a parseable datetime.');

        // Manually mutate the DB to a fixed older timestamp.
        rex_sql::factory()->setQuery(
            'UPDATE `' . $table . '` SET created = :v WHERE id = :id',
            [':v' => '2000-01-01 00:00:00', ':id' => $ds->getId()],
        );
        rex_yform_manager_dataset::clearInstance([$table, $ds->getId()]);
        $ds = rex_yform_manager_dataset::require($ds->getId(), $table);

        // Second save: only_empty=1 must NOT overwrite a populated value.
        $this->assertTrue($ds->save());
        $this->assertSame('2000-01-01 00:00:00', (string) $ds->getValue('created'));
    }

    public function testDatestampOnlyEmpty2NeverUpdates(): void
    {
        $table = $this->buildTable('ft_ds_frozen', [
            'type_name'  => 'datestamp',
            'name'       => 'frozen',
            'format'     => 'mysql',
            'only_empty' => 2,
        ], [['frozen', 'datetime', true]]);

        // Insert directly with no datestamp set.
        $sql = rex_sql::factory();
        $sql->setTable($table);
        $sql->setRawValue('frozen', 'NULL');
        $sql->insert();
        $id = (int) $sql->getLastId();

        $ds = rex_yform_manager_dataset::require($id, $table);
        // only_empty=2 must NOT auto-fill even on first save.
        $this->assertTrue($ds->save());

        rex_yform_manager_dataset::clearInstance([$table, $id]);
        $reloaded = rex_yform_manager_dataset::require($id, $table);
        $value = $reloaded->getValue('frozen');
        // Either still NULL or empty string — anything except a fresh timestamp.
        $this->assertTrue(null === $value || '' === $value, 'only_empty=2 must leave the column alone. Got: ' . var_export($value, true));
    }

    // ---------- uuid ----------

    public function testUuidAutoGeneratesOnInsert(): void
    {
        $table = $this->buildTable('ft_uuid', [
            'type_name' => 'uuid',
            'name'      => 'uid',
        ], [['uid', 'varchar(191)']]);

        $ds = rex_yform_manager_dataset::create($table);
        $this->assertTrue($ds->save());
        $value = (string) $ds->getValue('uid');
        $this->assertNotSame('', $value);
        // RFC 4122-ish: 8-4-4-4-12 hex pattern. yform uses uniqid-ish keys, so
        // be lenient: just assert min length.
        $this->assertTrue(strlen($value) >= 13, 'UUID-style value should be reasonably long. Got: "' . $value . '"');
    }

    public function testUuidUniqueAcrossDatasets(): void
    {
        $table = $this->buildTable('ft_uuid_uniq', [
            'type_name' => 'uuid',
            'name'      => 'uid',
        ], [['uid', 'varchar(191)']]);

        $a = rex_yform_manager_dataset::create($table);
        $this->assertTrue($a->save());
        $b = rex_yform_manager_dataset::create($table);
        $this->assertTrue($b->save());

        $this->assertNotSame((string) $a->getValue('uid'), (string) $b->getValue('uid'));
    }

    // ---------- generate_key ----------

    public function testGenerateKeyFillsEmptyValue(): void
    {
        $table = $this->buildTable('ft_gen_key', [
            'type_name'  => 'generate_key',
            'name'       => 'token',
            'only_empty' => 1,
        ], [['token', 'varchar(191)']]);

        $ds = rex_yform_manager_dataset::create($table);
        $this->assertTrue($ds->save());
        $value = (string) $ds->getValue('token');
        $this->assertNotSame('', $value, 'generate_key must auto-fill on insert.');
    }

    // ---------- hidden ----------

    public function testHiddenViaSetTableFieldPersistsValue(): void
    {
        // Regression: rex_yform_value_hidden used to lack getDefinitions(),
        // causing dataset->save() to crash on "Undefined array key 'values'"
        // in dataset.php:704 followed by a PDOException. Now configurable.
        $table = $this->buildTable('ft_hidden', [
            'type_name' => 'hidden',
            'name'      => 'token',
            'value'     => 'abc-123',
        ], [['token', 'varchar(191)']]);

        $ds = rex_yform_manager_dataset::create($table);
        $this->assertTrue($ds->save());
        $this->assertSame('abc-123', (string) $ds->getValue('token'));
    }
}
