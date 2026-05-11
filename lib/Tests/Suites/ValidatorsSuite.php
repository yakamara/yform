<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform_manager_dataset;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

/**
 * Tests for the YForm validator types.
 * Covers §3.U.5 from .claude/plans/02-test-strategy.md.
 *
 * Strategy: each test creates a one-off fixture table containing exactly the
 * field(s) needed for the validator under test, then exercises the validator
 * by calling rex_yform_manager_dataset::save() with passing and failing values.
 * The dataset's getMessages() reflects the validator's verdict.
 *
 * @package redaxo\yform
 * @internal
 */
final class ValidatorsSuite extends AbstractTestSuite
{
    /**
     * Builds a fixture table with the given fields + validators.
     *
     * @param array<int, array<string, mixed>> $valueFields    list of value-field defs (type_name, name, label, db_type-ish)
     * @param array<int, array<string, mixed>> $validators     list of validator defs
     * @param array<int, array{0: string, 1: string, 2?: bool}> $sqlColumns  list of [name, sql_type, nullable]
     */
    private function buildTable(string $shortName, array $valueFields, array $validators, array $sqlColumns): rex_yform_manager_table
    {
        $tableName = $this->fixtures->reserveTableName($shortName);

        // Force fresh schema — defends against leftover columns from earlier runs.
        try {
            rex_yform_manager_table_api::removeTable($tableName);
        } catch (\Throwable) {}
        try {
            rex_sql_table::get($tableName)->drop();
        } catch (\Throwable) {}

        $tableBuilder = rex_sql_table::get($tableName)->ensurePrimaryIdColumn();
        foreach ($sqlColumns as $col) {
            $name = $col[0];
            $type = $col[1];
            $nullable = $col[2] ?? true;
            $tableBuilder->ensureColumn(new rex_sql_column($name, $type, $nullable));
        }
        $tableBuilder->ensure();

        $allFields = [];
        $prio = 1;
        foreach ($valueFields as $field) {
            $field['type_id']   = 'value';
            $field['prio']      = $prio++;
            $field['label']    ??= $field['name'];
            $allFields[] = $field;
        }
        foreach ($validators as $validator) {
            $validator['type_id'] = 'validate';
            $validator['prio']    = $prio++;
            $allFields[] = $validator;
        }

        rex_yform_manager_table_api::setTable([
            'table_name' => $tableName,
            'name'       => $shortName,
            'status'     => 1,
            'hidden'     => 1,
        ], $allFields);

        $this->trackFixture($tableName);
        rex_yform_manager_table::deleteCache();

        return rex_yform_manager_table::require($tableName);
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

    // ---------- empty ----------

    public function testEmptyValidatorFailsOnEmptyValue(): void
    {
        $table = $this->buildTable(
            'v_empty',
            [['type_name' => 'text', 'name' => 'title']],
            [['type_name' => 'empty', 'name' => 'title', 'message' => 'Title required.']],
            [['title', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', '');
        $this->assertFalse($ds->save());
        $this->assertTrue(count($ds->getMessages()) > 0);
        $this->assertStringContains('Title required', implode(' ', $ds->getMessages()));
    }

    public function testEmptyValidatorPassesOnFilledValue(): void
    {
        $table = $this->buildTable(
            'v_empty_ok',
            [['type_name' => 'text', 'name' => 'title']],
            [['type_name' => 'empty', 'name' => 'title', 'message' => 'Title required.']],
            [['title', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', 'present');
        $this->assertTrue($ds->save(), 'Save should succeed for filled value. Messages: ' . implode(' ', $ds->getMessages()));
    }

    // ---------- type ----------

    public function testTypeValidatorEmailRejectsInvalid(): void
    {
        // Finding: the `type` validator reads getElement('type'), NOT
        // 'type_name_internal' as the skill doc suggested. Anyone passing
        // 'type_name_internal' gets a silent no-op.
        $table = $this->buildTable(
            'v_type_email',
            [['type_name' => 'text', 'name' => 'email']],
            [['type_name' => 'type', 'name' => 'email', 'type' => 'email', 'message' => 'Not an email.']],
            [['email', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('email', 'not-an-email');
        $this->assertFalse($ds->save());

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('email', 'good@example.com');
        $this->assertTrue($ds->save());
    }

    public function testTypeValidatorIntRejectsNonNumeric(): void
    {
        $table = $this->buildTable(
            'v_type_int',
            [['type_name' => 'text', 'name' => 'age']],
            [['type_name' => 'type', 'name' => 'age', 'type' => 'int', 'message' => 'Not an int.']],
            [['age', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('age', 'abc');
        $this->assertFalse($ds->save());

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('age', '42');
        $this->assertTrue($ds->save());
    }

    // ---------- compare_value ----------

    public function testCompareValueWithEqualsOperator(): void
    {
        // Finding: param keys are 'compare_value' + 'compare_type' (NOT
        // 'compare_operator' as the skill doc claimed).
        // Plus: compare_value behavior is INVERTED — '==' produces an error when
        // the value EQUALS the literal. Default operator '!=' is also inverted:
        // it errors when the value differs. So this asserts: value != 'yes' OK,
        // value == 'yes' ERROR. (Likely a misnamed validator; semantically it
        // means "must NOT equal" by default. Investigate later.)
        $table = $this->buildTable(
            'v_cmp_val',
            [['type_name' => 'text', 'name' => 'flag']],
            [['type_name' => 'compare_value', 'name' => 'flag', 'compare_value' => 'forbidden', 'compare_type' => '==', 'message' => 'value forbidden']],
            [['flag', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('flag', 'forbidden');
        $this->assertFalse($ds->save(), 'compare_value with "==" errors when EQUAL — inverse of intuition.');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('flag', 'allowed');
        $this->assertTrue($ds->save());
    }

    // ---------- compare (two fields) ----------

    public function testCompareTwoFieldsEqual(): void
    {
        // Finding: param keys are 'name' + 'name2' + 'compare_type' (NOT 'name1'/
        // 'name2'/'compare_operator' as the skill doc claimed). Plus: same
        // inverted semantics as compare_value — '==' errors when fields are
        // EQUAL. So "passwords must match" needs compare_type='!=' (errors when
        // they differ).
        $table = $this->buildTable(
            'v_cmp_two',
            [
                ['type_name' => 'text', 'name' => 'pw1'],
                ['type_name' => 'text', 'name' => 'pw2'],
            ],
            [['type_name' => 'compare', 'name' => 'pw1', 'name2' => 'pw2', 'compare_type' => '!=', 'message' => 'passwords differ']],
            [
                ['pw1', 'varchar(191)'],
                ['pw2', 'varchar(191)'],
            ],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('pw1', 'aaa');
        $ds->setValue('pw2', 'bbb');
        $this->assertFalse($ds->save(), 'compare_type=!= errors when fields differ — inverse of intuition.');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('pw1', 'same');
        $ds->setValue('pw2', 'same');
        $this->assertTrue($ds->save());
    }

    // ---------- intfromto ----------

    public function testIntFromToValidatorEnforcesRange(): void
    {
        $table = $this->buildTable(
            'v_intfromto',
            [['type_name' => 'text', 'name' => 'qty']],
            [['type_name' => 'intfromto', 'name' => 'qty', 'from' => 10, 'to' => 20, 'message' => 'qty out of range']],
            [['qty', 'varchar(191)']],
        );

        foreach ([5 => false, 10 => true, 15 => true, 20 => true, 21 => false] as $val => $shouldPass) {
            $ds = rex_yform_manager_dataset::create($table->getTableName());
            $ds->setValue('qty', (string) $val);
            $ok = $ds->save();
            $this->assertSame($shouldPass, $ok, "qty={$val} expected " . ($shouldPass ? 'pass' : 'fail'));
            // cleanup if it got saved
            if ($ok) {
                $ds->delete();
            }
        }
    }

    // ---------- size_range ----------

    public function testSizeRangeValidator(): void
    {
        $table = $this->buildTable(
            'v_size',
            [['type_name' => 'text', 'name' => 'code']],
            [['type_name' => 'size_range', 'name' => 'code', 'min' => 3, 'max' => 5, 'message' => 'size out of range']],
            [['code', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('code', 'ab');
        $this->assertFalse($ds->save());

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('code', 'abcdef');
        $this->assertFalse($ds->save());

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('code', 'abcd');
        $this->assertTrue($ds->save());
    }

    // ---------- preg_match ----------

    public function testPregMatchValidator(): void
    {
        $table = $this->buildTable(
            'v_preg',
            [['type_name' => 'text', 'name' => 'code']],
            [['type_name' => 'preg_match', 'name' => 'code', 'pattern' => '/^[A-Z]{3}$/', 'message' => 'must be 3 uppercase letters']],
            [['code', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('code', 'abc');
        $this->assertFalse($ds->save());

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('code', 'ABC');
        $this->assertTrue($ds->save());
    }

    // ---------- unique ----------

    public function testUniqueValidatorRejectsDuplicate(): void
    {
        $table = $this->buildTable(
            'v_unique',
            [['type_name' => 'text', 'name' => 'email']],
            [['type_name' => 'unique', 'name' => 'email', 'message' => 'email taken']],
            [['email', 'varchar(191)']],
        );

        $a = rex_yform_manager_dataset::create($table->getTableName());
        $a->setValue('email', 'first@example.com');
        $this->assertTrue($a->save());

        $b = rex_yform_manager_dataset::create($table->getTableName());
        $b->setValue('email', 'first@example.com');
        $this->assertFalse($b->save());

        $c = rex_yform_manager_dataset::create($table->getTableName());
        $c->setValue('email', 'second@example.com');
        $this->assertTrue($c->save());
    }

    public function testUniqueValidatorAllowsUpdateOnOwnRow(): void
    {
        $table = $this->buildTable(
            'v_unique_upd',
            [['type_name' => 'text', 'name' => 'email']],
            [['type_name' => 'unique', 'name' => 'email', 'message' => 'email taken']],
            [['email', 'varchar(191)']],
        );

        $a = rex_yform_manager_dataset::create($table->getTableName());
        $a->setValue('email', 'sole@example.com');
        $this->assertTrue($a->save());

        // Same value, same row: save() must succeed (unique skips self).
        $a->setValue('email', 'sole@example.com');
        $this->assertTrue($a->save(), 'Update with unchanged unique value on own row must succeed. Got: ' . implode(' ', $a->getMessages()));
    }

    // ---------- customfunction ----------

    public function testCustomFunctionValidatorTrueMeansError(): void
    {
        $table = $this->buildTable(
            'v_custom',
            [['type_name' => 'text', 'name' => 'qty']],
            [['type_name' => 'customfunction', 'name' => 'qty', 'function' => self::class . '::customLessThanTen', 'message' => 'must be >= 10']],
            [['qty', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('qty', '5');
        $this->assertFalse($ds->save(), 'customfunction returned true (=error) for qty<10, save must fail.');

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('qty', '42');
        $this->assertTrue($ds->save(), 'customfunction returned false (=ok) for qty>=10, save must succeed.');
    }

    /**
     * Custom validation callback used by testCustomFunctionValidatorTrueMeansError.
     * Returns TRUE when the value is an error (i.e. < 10).
     *
     * Finding: customfunction's actual callback signature (see
     * rex_yform_validate_customfunction::customfunction_execute) is
     *   call_user_func($func, $names, $values, $parameter, $validator, $Objects)
     * — NOT the ($label, $value, $params, $return) shown in the skill doc.
     * The third arg is whatever the `params` element holds (string|null), not
     * a structured array. Type your callback's third arg as `mixed`.
     */
    public static function customLessThanTen(mixed $names, mixed $value, mixed $parameter = null, mixed $validator = null, mixed $objects = null): bool
    {
        return (int) $value < 10;
    }

    // ---------- password_policy ----------

    public function testPasswordPolicyValidatorWithDefaultRules(): void
    {
        $table = $this->buildTable(
            'v_pwd',
            [['type_name' => 'text', 'name' => 'pwd']],
            [['type_name' => 'password_policy', 'name' => 'pwd', 'message' => 'weak password']],
            [['pwd', 'varchar(191)']],
        );

        // "weak" — too short, no symbols / uppercase / digits.
        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('pwd', 'weak');
        $this->assertFalse($ds->save());

        // Strong password matching default policy.
        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('pwd', 'StrongPwd!123');
        $this->assertTrue($ds->save(), 'Strong password should pass default policy. Got: ' . implode(' ', $ds->getMessages()));
    }

    // ---------- in_table ----------

    public function testInTableValidatorPassesWhenReferenceExists(): void
    {
        // Build a small "reference" table (the lookup target) and a "main" table
        // whose `category` value must exist as `slug` in the reference table.
        $refTable = $this->fixtures->reserveTableName('v_in_table_ref');
        try { rex_yform_manager_table_api::removeTable($refTable); } catch (\Throwable) {}
        try { rex_sql_table::get($refTable)->drop(); } catch (\Throwable) {}
        rex_sql_table::get($refTable)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('slug', 'varchar(191)', false))
            ->ensure();
        rex_sql::factory()->setQuery('INSERT INTO ' . $refTable . ' (slug) VALUES (:s)', [':s' => 'news']);
        $this->trackFixture($refTable);

        $mainTable = $this->buildTable(
            'v_in_table_main',
            [['type_name' => 'text', 'name' => 'category']],
            [[
                'type_name' => 'in_table',
                'name'      => 'category',
                'table'     => $refTable,
                'fields'    => 'slug',
                'message'   => 'Unknown category.',
            ]],
            [['category', 'varchar(191)']],
        );

        $ok = rex_yform_manager_dataset::create($mainTable->getTableName());
        $ok->setValue('category', 'news');
        $this->assertTrue($ok->save(), 'Existing reference must pass: ' . implode(' ', $ok->getMessages()));

        $bad = rex_yform_manager_dataset::create($mainTable->getTableName());
        $bad->setValue('category', 'does-not-exist');
        $this->assertFalse($bad->save(), 'Missing reference must fail.');
        $this->assertStringContains('Unknown category.', implode(' || ', $bad->getMessages()));
    }

    public function testValidatorMessageEndsUpInGetMessages(): void
    {
        $table = $this->buildTable(
            'v_msg',
            [['type_name' => 'text', 'name' => 'title']],
            [['type_name' => 'empty', 'name' => 'title', 'message' => 'You must provide a title.']],
            [['title', 'varchar(191)']],
        );

        $ds = rex_yform_manager_dataset::create($table->getTableName());
        $ds->setValue('title', '');
        $ds->save();
        $this->assertStringContains('You must provide a title.', implode(' || ', $ds->getMessages()));
    }
}
