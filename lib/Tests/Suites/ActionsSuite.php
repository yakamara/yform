<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use rex;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform;

/**
 * Tests for the YForm action types.
 * Covers §3.U.6 from .claude/plans/02-test-strategy.md.
 *
 * Strategy: drive rex_yform directly via PHP API. Each test builds a fresh
 * rex_yform instance with the relevant value/action fields, then triggers
 * the pipeline by setting send=1 and calling getForm(). Assert side effects:
 *   - db / db_query / manage_db   → row in DB
 *   - tpl2email / email           → MailerStub captures the mail
 *   - callback                    → static flag flipped
 *   - showtext                    → string appears in objparams.output
 *
 * Form objparams used everywhere:
 *   real_field_names = true     (input keys = field names — cleaner under test)
 *   form_needs_output = false   (no HTML rendering)
 *   csrf_protection = false     (no CSRF check)
 *   form_exit = false           (don't exit() after success)
 *
 * @package redaxo\yform
 * @internal
 */
final class ActionsSuite extends AbstractTestSuite
{
    /** Flag set by testCallbackActionRuns. */
    public static bool $callbackFired = false;

    /** Counter for testCallbackActionRuns. */
    public static int $callbackCount = 0;

    /**
     * Build a fresh rex_yform configured for headless testing.
     */
    private function freshForm(string $formNameSuffix = ''): rex_yform
    {
        $yform = new rex_yform();
        $yform->setObjectparams('form_name', 'tactions_' . $formNameSuffix . '_' . substr(uniqid(), -6));
        $yform->setObjectparams('real_field_names', true);
        $yform->setObjectparams('form_needs_output', false);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('form_exit', false);
        return $yform;
    }

    /**
     * Build a fresh fixture table (drop+recreate) with the given SQL columns.
     *
     * @param list<array{0:string,1:string,2?:bool}> $columns
     */
    private function makeFixtureTable(string $shortName, array $columns): string
    {
        $tableName = $this->fixtures->reserveTableName($shortName);
        try { rex_sql_table::get($tableName)->drop(); } catch (\Throwable) {}

        $b = rex_sql_table::get($tableName)->ensurePrimaryIdColumn();
        foreach ($columns as $col) {
            $b->ensureColumn(new rex_sql_column($col[0], $col[1], $col[2] ?? true));
        }
        $b->ensure();

        $this->trackFixture($tableName);
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

    public function setUp(): void
    {
        self::$callbackFired = false;
        self::$callbackCount = 0;
        $this->mailer->reset();
    }

    // ---------- db ----------

    public function testDbActionInsertsRow(): void
    {
        $table = $this->makeFixtureTable('a_db_ins', [
            ['title', 'varchar(191)'],
        ]);

        $yform = $this->freshForm('db_ins');
        $yform->setObjectparams('data', ['title' => 'Hello DB']);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('db', [$table]);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        // Finding: actions_executed is the BOOLEAN `true`, not the int `1`.
        $this->assertTrue((bool) $yform->objparams['actions_executed']);
        $rows = rex_sql::factory()->getArray('SELECT * FROM `' . $table . '`');
        $this->assertCount(1, $rows);
        $this->assertSame('Hello DB', $rows[0]['title']);
    }

    public function testDbActionUpdatesRowWithMainWhere(): void
    {
        $table = $this->makeFixtureTable('a_db_upd', [
            ['title', 'varchar(191)'],
        ]);

        // Seed one row.
        rex_sql::factory()->setTable($table)->setValue('title', 'before')->insert();

        $yform = $this->freshForm('db_upd');
        $yform->setObjectparams('data', ['title' => 'after']);
        $yform->setObjectparams('main_where', 'id=1');
        $yform->setObjectparams('main_id', 1);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('db', [$table, 'main_where']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $row = rex_sql::factory()->getArray('SELECT title FROM `' . $table . '` WHERE id = 1');
        $this->assertSame('after', $row[0]['title']);
    }

    // ---------- callback ----------

    public function testCallbackActionRuns(): void
    {
        $yform = $this->freshForm('cb');
        $yform->setObjectparams('data', ['x' => 'unused']);
        $yform->setValueField('text', ['x', 'X']);
        $yform->setActionField('callback', [self::class . '::recordCallback']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertTrue(self::$callbackFired);
        $this->assertSame(1, self::$callbackCount);
    }

    /**
     * Used by testCallbackActionRuns.
     *
     * Finding: the callback receives the ACTION OBJECT (an instance of
     * rex_yform_action_callback), NOT a rex_yform instance as the skill doc
     * suggested. To get the rex_yform, read $action->params['this'].
     */
    public static function recordCallback(mixed $actionObject): void
    {
        self::$callbackFired = true;
        ++self::$callbackCount;
    }

    // ---------- showtext ----------

    public function testShowtextActionAppendsToOutput(): void
    {
        $yform = $this->freshForm('show');
        $yform->setObjectparams('data', ['x' => 'foo']);
        $yform->setObjectparams('form_needs_output', true); // showtext writes into output
        $yform->setValueField('text', ['x', 'X']);
        $yform->setActionField('showtext', ['Thanks for ###x###!', '', '', '0']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertStringContains('Thanks for foo!', $yform->objparams['output']);
    }

    // ---------- manage_db ----------

    // ---------- manage_db ----------
    // Finding: despite the name, rex_yform_action_manage_db does NOT auto-detect
    // whether a matching row exists. It simply checks whether the 2nd action arg
    // (slot 3 — the where clause) is non-empty. If yes → UPDATE; if no → INSERT.
    // The action also does NOT honor the literal 'main_where' that the `db` action
    // special-cases. Pass the actual SQL where literal as 2nd arg.

    public function testManageDbInsertsWhenWhereIsEmpty(): void
    {
        $table = $this->makeFixtureTable('a_manage_ins', [
            ['title', 'varchar(191)'],
        ]);

        $yform = $this->freshForm('manage_ins');
        $yform->setObjectparams('data', ['title' => 'new']);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('manage_db', [$table]); // no where -> insert
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $rows = rex_sql::factory()->getArray('SELECT * FROM `' . $table . '`');
        $this->assertCount(1, $rows);
    }

    public function testManageDbUpdatesWhenWhereProvided(): void
    {
        $table = $this->makeFixtureTable('a_manage_upd', [
            ['title', 'varchar(191)'],
        ]);
        rex_sql::factory()->setTable($table)->setValue('title', 'before')->insert();

        $yform = $this->freshForm('manage_upd');
        $yform->setObjectparams('data', ['title' => 'after']);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('manage_db', [$table, 'id=1']); // literal where -> update
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $rows = rex_sql::factory()->getArray('SELECT title FROM `' . $table . '`');
        $this->assertCount(1, $rows, 'manage_db must not insert a 2nd row when where matches an existing one.');
        $this->assertSame('after', $rows[0]['title']);
    }

    // ---------- copy_value ----------

    public function testCopyValueAction(): void
    {
        // Finding: copy_value writes to value_pool.sql, NOT value_pool.email.
        // The skill doc didn't make this explicit.
        $yform = $this->freshForm('copy');
        $yform->setObjectparams('data', ['src' => 'original', 'dst' => '']);
        $yform->setValueField('text', ['src', 'Src']);
        $yform->setValueField('text', ['dst', 'Dst']);
        $yform->setActionField('copy_value', ['src', 'dst']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertSame('original', $yform->objparams['value_pool']['sql']['dst'] ?? null);
    }

    // ---------- tpl2email ----------

    public function testTpl2EmailActionSendsMail(): void
    {
        // Create an email template just for this test.
        $tplName = 'unittest_tpl_' . substr(uniqid(), -6);
        rex_sql::factory()
            ->setTable(rex::getTable('yform_email_template'))
            ->setValue('name', $tplName)
            ->setValue('mail_from', 'sender@example.com')
            ->setValue('mail_from_name', 'Sender')
            ->setValue('subject', 'Hi REX_YFORM_DATA[field="name"]')
            ->setValue('body', 'Hello REX_YFORM_DATA[field="name"]')
            ->setValue('body_html', '')
            ->setValue('attachments', '')
            ->insert();

        try {
            $yform = $this->freshForm('tpl');
            $yform->setObjectparams('data', ['name' => 'Tester', 'email' => 'recipient@example.com']);
            $yform->setValueField('text',  ['name', 'Name']);
            $yform->setValueField('email', ['email', 'Email']);
            $yform->setActionField('tpl2email', [$tplName, 'email', 'name']);
            $yform->setFieldValue('send', [], '1');

            $yform->getForm();

            // MailerStub captures via YFORM_EMAIL_BEFORE_SEND.
            $this->assertSame(1, $this->mailer->count());
            $last = $this->mailer->lastMail();
            $this->assertNotNull($last);
            $this->assertStringContains('Tester', (string) $last['body']);
            $this->assertSame('recipient@example.com', $last['mail_to'] ?? null);
        } finally {
            rex_sql::factory()->setQuery(
                'DELETE FROM ' . rex::getTable('yform_email_template') . ' WHERE name = :n',
                [':n' => $tplName],
            );
        }
    }

    public function testTpl2EmailWithMissingTemplateSilentlyNoOps(): void
    {
        // Finding: tpl2email without a matching template just silently no-ops.
        // No exception, no warning_message, just nothing happens.
        $yform = $this->freshForm('tpl_missing');
        $yform->setObjectparams('data', ['email' => 'recipient@example.com']);
        $yform->setValueField('email', ['email', 'Email']);
        $yform->setActionField('tpl2email', ['this_template_does_not_exist', 'email', '']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        // actions_executed still true (the action ran, it just did nothing).
        $this->assertTrue((bool) $yform->objparams['actions_executed']);
        $this->assertSame(0, $this->mailer->count(), 'No mail should be sent for missing template.');
    }

    // ---------- redirect ----------

    public function testRedirectActionKnownIssueExitsProcess(): void
    {
        // rex_yform_action_redirect calls rex_response::sendCacheControl(),
        // sendContent() and exit() — there's no way to test it under a normal
        // test harness without forking. Documented for completeness.
        $this->markSkipped(
            'redirect action calls exit() — not testable without process isolation. '
          . 'Verify manually in the browser instead.',
        );
    }

    // ---------- db_query ----------

    public function testDbQueryActionWithPlaceholders(): void
    {
        $table = $this->makeFixtureTable('a_dbq', [
            ['name',  'varchar(191)'],
            ['status', 'int(11)'],
        ]);

        $yform = $this->freshForm('dbq');
        $yform->setObjectparams('data', ['name' => 'X', 'status' => '5']);
        $yform->setValueField('text', ['name', 'Name']);
        $yform->setValueField('text', ['status', 'Status']);
        $yform->setActionField('db_query', [
            'INSERT INTO `' . $table . '` (`name`, `status`) VALUES (?, ?)',
            'name,status',
        ]);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $rows = rex_sql::factory()->getArray('SELECT * FROM `' . $table . '`');
        $this->assertCount(1, $rows);
        $this->assertSame('X', $rows[0]['name']);
        $this->assertSame(5, (int) $rows[0]['status']);
    }
}
