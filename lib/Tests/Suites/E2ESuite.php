<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use ReflectionClass;
use rex;
use rex_addon;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform;
use Throwable;

/**
 * End-to-end tests against a real REDAXO boot.
 * Covers §3.E2E from .claude/plans/02-test-strategy.md.
 *
 * Strategy: drive realistic, multi-step scenarios that integration tests
 * don't fully cover — multi-field forms with validators + actions in
 * combination, full install.php lifecycle, action chains.
 *
 * @package redaxo\yform
 * @internal
 */
final class E2ESuite extends AbstractTestSuite
{
    public function setUp(): void
    {
        $this->mailer->reset();
    }

    private function freshForm(string $suffix = ''): rex_yform
    {
        $yform = new rex_yform();
        $yform->setObjectparams('form_name', 'e2e_' . $suffix . '_' . substr(uniqid(), -6));
        $yform->setObjectparams('real_field_names', true);
        $yform->setObjectparams('form_needs_output', false);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('form_exit', false);
        return $yform;
    }

    /**
     * @param list<array{0:string,1:string,2?:bool}> $columns
     */
    private function makeFixtureTable(string $shortName, array $columns): string
    {
        $tableName = $this->fixtures->reserveTableName($shortName);
        try {
            rex_sql_table::get($tableName)->drop();
        } catch (Throwable) {
        }
        $b = rex_sql_table::get($tableName)->ensurePrimaryIdColumn();
        foreach ($columns as $col) {
            $b->ensureColumn(new rex_sql_column($col[0], $col[1], $col[2] ?? true));
        }
        $b->ensure();

        $reflection = new ReflectionClass($this->fixtures);
        $prop = $reflection->getProperty('createdTables');
        $list = (array) $prop->getValue($this->fixtures);
        $list[] = $tableName;
        $prop->setValue($this->fixtures, $list);

        return $tableName;
    }

    // ---------- install lifecycle ----------

    public function testInstallScriptIsIdempotent(): void
    {
        // install.php uses rex_sql_table::ensure() which is idempotent by design.
        // Running it twice in succession must not throw and must leave the
        // expected system tables in place.
        $addon = rex_addon::get('yform');

        $addon->includeFile('install.php');
        $addon->includeFile('install.php');

        $expected = [
            rex::getTable('yform_table'),
            rex::getTable('yform_field'),
            rex::getTable('yform_email_template'),
            rex::getTable('yform_history'),
            rex::getTable('yform_history_field'),
            rex::getTable('yform_rest_token'),
        ];
        foreach ($expected as $table) {
            $exists = rex_sql_table::get($table)->exists();
            $this->assertTrue($exists, 'System table missing after install: ' . $table);
        }
    }

    // ---------- full form pipeline ----------

    public function testFullStackFormValidatesThenInsertsRow(): void
    {
        $table = $this->makeFixtureTable('e2e_form', [
            ['name', 'varchar(191)'],
            ['email', 'varchar(191)'],
        ]);

        // Submit 1: empty name → empty-validator must reject, no row inserted.
        $yform = $this->freshForm('empty');
        $yform->setObjectparams('data', ['name' => '', 'email' => 'someone@example.com']);
        $yform->setValueField('text', ['name', 'Name']);
        $yform->setValueField('email', ['email', 'Email']);
        $yform->setValidateField('empty', ['name', 'Name darf nicht leer sein']);
        $yform->setValidateField('type', ['email', 'email', '0', 'Ungültige Email']);
        $yform->setActionField('db', [$table]);
        $yform->setFieldValue('send', [], '1');
        $yform->getForm();
        $this->assertSame(0, (int) rex_sql::factory()->getArray('SELECT COUNT(*) AS c FROM `' . $table . '`')[0]['c']);

        // Submit 2: invalid email → type-validator rejects.
        $yform = $this->freshForm('badmail');
        $yform->setObjectparams('data', ['name' => 'Alice', 'email' => 'not-an-email']);
        $yform->setValueField('text', ['name', 'Name']);
        $yform->setValueField('email', ['email', 'Email']);
        $yform->setValidateField('empty', ['name', 'Name darf nicht leer sein']);
        $yform->setValidateField('type', ['email', 'email', '0', 'Ungültige Email']);
        $yform->setActionField('db', [$table]);
        $yform->setFieldValue('send', [], '1');
        $yform->getForm();
        $this->assertSame(0, (int) rex_sql::factory()->getArray('SELECT COUNT(*) AS c FROM `' . $table . '`')[0]['c']);

        // Submit 3: valid input → row inserted.
        $yform = $this->freshForm('ok');
        $yform->setObjectparams('data', ['name' => 'Alice', 'email' => 'alice@example.com']);
        $yform->setValueField('text', ['name', 'Name']);
        $yform->setValueField('email', ['email', 'Email']);
        $yform->setValidateField('empty', ['name', 'Name darf nicht leer sein']);
        $yform->setValidateField('type', ['email', 'email', '0', 'Ungültige Email']);
        $yform->setActionField('db', [$table]);
        $yform->setFieldValue('send', [], '1');
        $yform->getForm();

        $rows = rex_sql::factory()->getArray('SELECT name, email FROM `' . $table . '`');
        $this->assertCount(1, $rows);
        $this->assertSame('Alice', $rows[0]['name']);
        $this->assertSame('alice@example.com', $rows[0]['email']);
    }

    public function testFormSubmitTriggersBothDbInsertAndEmail(): void
    {
        $table = $this->makeFixtureTable('e2e_chain', [
            ['name', 'varchar(191)'],
            ['email', 'varchar(191)'],
        ]);

        $tplName = 'e2e_chain_tpl_' . substr(uniqid(), -6);
        rex_sql::factory()
            ->setTable(rex::getTable('yform_email_template'))
            ->setValue('name', $tplName)
            ->setValue('mail_from', 'noreply@example.com')
            ->setValue('mail_from_name', 'Sender')
            ->setValue('subject', 'Hi REX_YFORM_DATA[field="name"]')
            ->setValue('body', 'Welcome REX_YFORM_DATA[field="name"]')
            ->setValue('body_html', '')
            ->setValue('attachments', '')
            ->insert();

        try {
            $yform = $this->freshForm('chain');
            $yform->setObjectparams('data', ['name' => 'Bob', 'email' => 'bob@example.com']);
            $yform->setValueField('text', ['name', 'Name']);
            $yform->setValueField('email', ['email', 'Email']);
            $yform->setActionField('db', [$table]);
            $yform->setActionField('tpl2email', [$tplName, 'email', 'name']);
            $yform->setFieldValue('send', [], '1');

            $yform->getForm();

            // Action chain ran both: DB row + mail.
            $rows = rex_sql::factory()->getArray('SELECT name, email FROM `' . $table . '`');
            $this->assertCount(1, $rows);
            $this->assertSame('Bob', $rows[0]['name']);

            $this->assertSame(1, $this->mailer->count());
            $last = $this->mailer->lastMail();
            $this->assertNotNull($last);
            $this->assertSame('bob@example.com', $last['mail_to'] ?? null);
            $this->assertStringContains('Bob', (string) $last['body']);
        } finally {
            rex_sql::factory()->setQuery(
                'DELETE FROM ' . rex::getTable('yform_email_template') . ' WHERE name = :n',
                [':n' => $tplName],
            );
        }
    }
}
