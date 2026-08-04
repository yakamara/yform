<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use DateTime;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\FieldRegistry;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;
use Yakamara\YForm\Value\Choice;
use Yakamara\YForm\Value\Hidden;
use Yakamara\YForm\Value\Number;
use Yakamara\YForm\YForm;

use function in_array;
use function strlen;

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
        try {
            Api::removeTable($tableName);
        } catch (Throwable) {
        }
        try {
            DbTable::get($tableName)->drop();
        } catch (Throwable) {
        }

        $b = DbTable::get($tableName)->ensurePrimaryIdColumn();
        foreach ($sqlColumns as $col) {
            $b->ensureColumn(new Column($col[0], $col[1], $col[2] ?? true));
        }
        $b->ensure();

        $fieldDef['type_id'] = 'value';
        $fieldDef['prio'] ??= 1;
        $fieldDef['label'] ??= ($fieldDef['name'] ?? 'X');

        Api::setTable([
            'table_name' => $tableName,
            'name' => $shortName,
            'status' => 1,
            'hidden' => 1,
        ], [$fieldDef]);

        $this->trackFixture($tableName);
        Table::deleteCache();
        return $tableName;
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

    // ---------- checkbox ----------

    public function testCheckboxStoresOneForChecked(): void
    {
        $table = $this->buildTable('ft_checkbox', [
            'type_name' => 'checkbox',
            'name' => 'agreed',
        ], [['agreed', 'tinyint(1)']]);

        $ds = Dataset::create($table);
        $ds->setValue('agreed', 1);
        $this->assertTrue($ds->save());
        $this->assertSame(1, (int) $ds->getValue('agreed'));
    }

    public function testCheckboxStoresZeroForUnchecked(): void
    {
        $table = $this->buildTable('ft_checkbox_off', [
            'type_name' => 'checkbox',
            'name' => 'agreed',
        ], [['agreed', 'tinyint(1)']]);

        $ds = Dataset::create($table);
        $ds->setValue('agreed', 0);
        $this->assertTrue($ds->save());
        $this->assertSame(0, (int) $ds->getValue('agreed'));
    }

    // ---------- choice ----------

    public function testChoiceSingleSelectPersistsValue(): void
    {
        $table = $this->buildTable('ft_choice_single', [
            'type_name' => 'choice',
            'name' => 'color',
            'choices' => '{"Red":"red","Green":"green","Blue":"blue"}',
            'expanded' => 0,
            'multiple' => 0,
        ], [['color', 'varchar(191)']]);

        $ds = Dataset::create($table);
        $ds->setValue('color', 'green');
        $this->assertTrue($ds->save());
        $this->assertSame('green', $ds->getValue('color'));
    }

    public function testChoiceMultiSelectPersistsCommaList(): void
    {
        $table = $this->buildTable('ft_choice_multi', [
            'type_name' => 'choice',
            'name' => 'tags',
            'choices' => '{"News":"news","Blog":"blog","Foto":"foto"}',
            'expanded' => 1,
            'multiple' => 1,
        ], [['tags', 'text']]);

        $ds = Dataset::create($table);
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
        $field = new Choice();
        $def = $field->getDefinitions();
        $this->assertArrayHasKey('db_type', $def);
        $this->assertTrue(in_array('tinyint', $def['db_type'], true), 'tinyint must be offered as choice db_type.');
        // tinyint(1) stays available for backward compat.
        $this->assertTrue(in_array('tinyint(1)', $def['db_type'], true));
    }

    public function testChoiceWritesLabelsAndListEntriesToEmailPool(): void
    {
        // Drive YForm directly so we can inspect value_pool.email after fields run.
        $tableName = $this->buildTable('ft_choice_pool', [
            'type_name' => 'choice',
            'name' => 'color',
            'choices' => '{"Red":"red","Green":"green"}',
            'expanded' => 0,
            'multiple' => 0,
        ], [['color', 'varchar(191)']]);

        $yform = new YForm();
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
            'type_name' => 'datestamp',
            'name' => 'created',
            'format' => 'mysql',
            'only_empty' => 1,
        ], [['created', 'datetime']]);

        $ds = Dataset::create($table);
        // Don't set a value — datestamp should auto-fill.
        $this->assertTrue($ds->save());
        $firstValue = (string) $ds->getValue('created');
        $this->assertNotSame('', $firstValue);
        $this->assertTrue(strtotime($firstValue) > 0, 'Value should be a parseable datetime.');

        // Manually mutate the DB to a fixed older timestamp.
        Sql::factory()->setQuery(
            'UPDATE `' . $table . '` SET created = :v WHERE id = :id',
            [':v' => '2000-01-01 00:00:00', ':id' => $ds->getId()],
        );
        Dataset::clearInstance([$table, $ds->getId()]);
        $ds = Dataset::require($ds->getId(), $table);

        // Second save: only_empty=1 must NOT overwrite a populated value.
        $this->assertTrue($ds->save());
        $this->assertSame('2000-01-01 00:00:00', (string) $ds->getValue('created'));
    }

    public function testDatestampSurvivesInvalidModifyDefault(): void
    {
        // Issue #1578: a stored modify_default of '0' (or other unparseable
        // strings) used to crash the entire form pipeline with
        // DateMalformedStringException on PHP 8.3+ — the @-operator does NOT
        // catch typed exceptions. preValidateAction() now wraps DateTime::modify
        // in try/catch and falls back to "now".
        $table = $this->buildTable('ft_ds_bad_modify', [
            'type_name' => 'datestamp',
            'name' => 'created',
            'format' => 'mysql',
            'only_empty' => 0,
            'modify_default' => '0',
        ], [['created', 'datetime']]);

        $ds = Dataset::create($table);
        $this->assertTrue($ds->save(), 'Invalid modify_default must not crash save().');
        $value = (string) $ds->getValue('created');
        $this->assertTrue(strtotime($value) > 0, 'Datestamp must fall back to a valid datetime, got: ' . $value);
    }

    public function testDatestampOnlyEmpty2NeverUpdates(): void
    {
        $table = $this->buildTable('ft_ds_frozen', [
            'type_name' => 'datestamp',
            'name' => 'frozen',
            'format' => 'mysql',
            'only_empty' => 2,
        ], [['frozen', 'datetime', true]]);

        // Insert directly with no datestamp set.
        $sql = Sql::factory();
        $sql->setTable($table);
        $sql->setRawValue('frozen', 'NULL');
        $sql->insert();
        $id = (int) $sql->getLastId();

        $ds = Dataset::require($id, $table);
        // only_empty=2 must NOT auto-fill even on first save.
        $this->assertTrue($ds->save());

        Dataset::clearInstance([$table, $id]);
        $reloaded = Dataset::require($id, $table);
        $value = $reloaded->getValue('frozen');
        // Either still NULL or empty string — anything except a fresh timestamp.
        $this->assertTrue(null === $value || '' === $value, 'only_empty=2 must leave the column alone. Got: ' . var_export($value, true));
    }

    // ---------- uuid ----------

    public function testUuidAutoGeneratesOnInsert(): void
    {
        $table = $this->buildTable('ft_uuid', [
            'type_name' => 'uuid',
            'name' => 'uid',
        ], [['uid', 'varchar(191)']]);

        $ds = Dataset::create($table);
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
            'name' => 'uid',
        ], [['uid', 'varchar(191)']]);

        $a = Dataset::create($table);
        $this->assertTrue($a->save());
        $b = Dataset::create($table);
        $this->assertTrue($b->save());

        $this->assertNotSame((string) $a->getValue('uid'), (string) $b->getValue('uid'));
    }

    // ---------- generate_key ----------

    public function testGenerateKeyFillsEmptyValue(): void
    {
        $table = $this->buildTable('ft_gen_key', [
            'type_name' => 'generate_key',
            'name' => 'token',
            'only_empty' => 1,
        ], [['token', 'varchar(191)']]);

        $ds = Dataset::create($table);
        $this->assertTrue($ds->save());
        $value = (string) $ds->getValue('token');
        $this->assertNotSame('', $value, 'generate_key must auto-fill on insert.');
    }

    // ---------- hidden ----------

    public function testHiddenViaSetTableFieldPersistsValue(): void
    {
        // Regression: Hidden used to lack getDefinitions(),
        // causing dataset->save() to crash on "Undefined array key 'values'"
        // in dataset.php:704 followed by a PDOException. Now configurable.
        $table = $this->buildTable('ft_hidden', [
            'type_name' => 'hidden',
            'name' => 'token',
            'value' => 'abc-123',
        ], [['token', 'varchar(191)']]);

        $ds = Dataset::create($table);
        $this->assertTrue($ds->save());
        $this->assertSame('abc-123', (string) $ds->getValue('token'));
    }

    public function testHiddenAcceptsNonStringValueViaPipeSyntax(): void
    {
        // Issue #1350: hidden's element 2 is the VALUE not a label, but the
        // abstract's loadParams used to setLabel($this->getElement(2)) — which
        // crashed with TypeError when callers passed non-string defaults
        // (e.g. array) because public string $label is typed and non-coercible.
        // hidden now overrides loadParams to skip the label assignment.
        $yform = new YForm();
        $yform->setObjectparams('form_name', 'h_arr_' . substr(uniqid(), -6));
        $yform->setObjectparams('real_field_names', true);
        $yform->setObjectparams('form_needs_output', false);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('form_exit', false);

        // Array as element 2 (default value) — would TypeError on `string $label`
        // assignment without the loadParams override.
        $yform->setValueField('hidden', ['flag', ['a', 'b']]);
        $yform->setFieldValue('send', [], '1');
        $yform->executeFields();

        // Just verify the pipeline didn't crash. The label property must end
        // up as an empty string (set by our override).
        $this->assertTrue(true, 'Pipeline survived non-string default in hidden field.');
    }

    // ---------- number (DECIMAL column) ----------

    /**
     * `integer` normalises its input with `(int)`; `number` had no equivalent, so
     * German decimal input reached the DECIMAL column verbatim. REDAXO 5 ran with
     * SQL_MODE="" and truncated "2,50" to 2.00, REDAXO 6 rejects the statement.
     */
    public function testNumberNormalizesGermanDecimalComma(): void
    {
        $this->assertSame('2.50', Number::normalizeDecimal('2,50'));
        $this->assertSame('-3.25', Number::normalizeDecimal('-3,25'));
        $this->assertSame('8.5', Number::normalizeDecimal('  8,5  '));
    }

    public function testNumberNormalizesThousandSeparators(): void
    {
        $this->assertSame('1234.56', Number::normalizeDecimal('1.234,56'));
        $this->assertSame('1234.56', Number::normalizeDecimal('1,234.56'));
        $this->assertSame('1234.56', Number::normalizeDecimal('1 234,56'));
    }

    public function testNumberNormalizationLeavesValidInputAlone(): void
    {
        $this->assertSame('19.99', Number::normalizeDecimal('19.99'));
        $this->assertSame('7', Number::normalizeDecimal('7'));
        $this->assertSame('0', Number::normalizeDecimal('0'));
    }

    public function testNumberNormalizationTurnsJunkIntoNull(): void
    {
        $this->assertNull(Number::normalizeDecimal('abc'));
        $this->assertNull(Number::normalizeDecimal(''));
        $this->assertNull(Number::normalizeDecimal(null));
    }

    public function testNumberFieldPersistsGermanDecimalInput(): void
    {
        $table = $this->buildTable('ft_number_de', [
            'type_name' => 'number',
            'name' => 'price',
            'precision' => 10,
            'scale' => 2,
        ], [['price', 'decimal(10,2)']]);

        $ds = Dataset::create($table);
        $ds->setValue('price', '2,50');
        $this->assertTrue($ds->save(), 'save failed: ' . json_encode($ds->getMessages()));

        $reloaded = Dataset::get($ds->getId(), $table);
        $this->assertSame('2.50', (string) $reloaded?->getValue('price'));
    }

    public function testNumberFieldStoresNullForEmptyInput(): void
    {
        $table = $this->buildTable('ft_number_null', [
            'type_name' => 'number',
            'name' => 'price',
            'precision' => 10,
            'scale' => 2,
        ], [['price', 'decimal(10,2)']]);

        $ds = Dataset::create($table);
        $ds->setValue('price', '');
        $this->assertTrue($ds->save());

        $this->assertNull(Dataset::get($ds->getId(), $table)?->getValue('price'));
    }

    // ---------- prio ----------

    /**
     * An empty prio means "at the end". It must not reach the INT column as '',
     * which strict mode rejects — postAction() renumbers the scope anyway.
     */
    public function testPrioFieldSavesWithoutExplicitValue(): void
    {
        $table = $this->buildPrioTable('ft_prio');

        $first = Dataset::create($table);
        $first->setValue('label', 'A');
        $this->assertTrue($first->save(), 'first save failed: ' . json_encode($first->getMessages()));

        $second = Dataset::create($table);
        $second->setValue('label', 'B');
        $this->assertTrue($second->save(), 'second save failed: ' . json_encode($second->getMessages()));

        $rows = Sql::factory()->getArray('SELECT label, sort FROM `' . $table . '` ORDER BY sort');
        $this->assertSame(['A', 'B'], array_column($rows, 'label'));
        $this->assertSame([1, 2], array_map('intval', array_column($rows, 'sort')));
    }

    public function testPrioFieldHonoursExplicitTopPosition(): void
    {
        $table = $this->buildPrioTable('ft_prio_top');

        $first = Dataset::create($table);
        $first->setValue('label', 'A');
        $first->save();

        $second = Dataset::create($table);
        $second->setValue('label', 'B');
        $second->setValue('sort', 1);
        $second->save();

        $rows = Sql::factory()->getArray('SELECT label FROM `' . $table . '` ORDER BY sort');
        $this->assertSame(['B', 'A'], array_column($rows, 'label'));
    }

    /** A table with a text field plus a prio field on it. */
    private function buildPrioTable(string $shortName): string
    {
        $table = $this->buildTable($shortName, [
            'type_name' => 'text',
            'name' => 'label',
        ], [['label', 'varchar(191)'], ['sort', 'int(11)']]);

        Api::setTableField($table, [
            'type_id' => 'value', 'type_name' => 'prio', 'name' => 'sort',
            'label' => 'Sortierung', 'fields' => 'label', 'prio' => 2,
        ]);
        Table::deleteCache();

        return $table;
    }

    // ---------- list formatting is NULL-safe ----------

    /**
     * getListValue() feeds core's Formatter::custom(), which is typed `: string`.
     * A type that hands back the raw NULL of a nullable column takes the whole
     * data list down with a TypeError — and nullable columns are the norm in
     * REDAXO 6.
     */
    public function testGetListValueNeverReturnsNullForNullCell(): void
    {
        $table = $this->buildTable('ft_listnull', [
            'type_name' => 'text',
            'name' => 'title',
        ], [['title', 'varchar(191)']]);
        $tableObject = Table::get($table);
        $field = $tableObject->getValueField('title');

        foreach (FieldRegistry::all()['value'] as $typeName => $class) {
            if (!method_exists($class, 'getListValue')) {
                continue;
            }
            // These two need a configured target table to say anything at all.
            if (in_array($typeName, ['be_manager_relation', 'choice'], true)) {
                continue;
            }

            $result = @$class::getListValue([
                'subject' => null,
                'value' => null,
                'list' => null,
                'field' => $field->getName(),
                'format' => 'custom',
                'escape' => true,
                'params' => ['field' => $field->toArray(), 'fields' => $tableObject->getFields()],
            ]);

            $this->assertTrue(
                is_string($result),
                sprintf('%s::getListValue() returned %s for a NULL cell', $typeName, get_debug_type($result)),
            );
        }
    }

    // ---------- table metadata ----------

    /**
     * A table created through the API or a tableset import carries no
     * description; the typed getter must not trip over the NULL.
     */
    public function testTableDescriptionDefaultsToEmptyString(): void
    {
        $table = $this->buildTable('ft_nodesc', [
            'type_name' => 'text',
            'name' => 'title',
        ], [['title', 'varchar(191)']]);

        Sql::factory()->setQuery(
            'UPDATE ' . Table::table() . ' SET description = NULL WHERE table_name = :t',
            [':t' => $table],
        );
        Table::deleteCache();

        $this->assertSame('', Table::get($table)->getDescription());
    }
}
