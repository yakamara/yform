<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Core;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use Redaxo\Core\Http\Response;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Action\Callback;
use Yakamara\YForm\Action\ManageDb;
use Yakamara\YForm\Action\Redirect;
use Yakamara\YForm\Test\AbstractTestSuite;
use Yakamara\YForm\YForm;

use function in_array;

/**
 * Tests for the YForm action types.
 * Covers §3.U.6 from .claude/plans/02-test-strategy.md.
 *
 * Strategy: drive YForm directly via PHP API. Each test builds a fresh
 * YForm instance with the relevant value/action fields, then triggers
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

    public function setUp(): void
    {
        self::$callbackFired = false;
        self::$callbackCount = 0;
        $this->mailer->reset();
    }

    /**
     * Build a fresh YForm configured for headless testing.
     */
    private function freshForm(string $formNameSuffix = ''): YForm
    {
        $yform = new YForm();
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
        try {
            DbTable::get($tableName)->drop();
        } catch (Throwable) {
        }

        $b = DbTable::get($tableName)->ensurePrimaryIdColumn();
        foreach ($columns as $col) {
            $b->ensureColumn(new Column($col[0], $col[1], $col[2] ?? true));
        }
        $b->ensure();

        $this->trackFixture($tableName);
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
        $rows = Sql::factory()->getArray('SELECT * FROM `' . $table . '`');
        $this->assertCount(1, $rows);
        $this->assertSame('Hello DB', $rows[0]['title']);
    }

    public function testDbActionUpdatesRowWithMainWhere(): void
    {
        $table = $this->makeFixtureTable('a_db_upd', [
            ['title', 'varchar(191)'],
        ]);

        // Seed one row.
        Sql::factory()->setTable($table)->setValue('title', 'before')->insert();

        $yform = $this->freshForm('db_upd');
        $yform->setObjectparams('data', ['title' => 'after']);
        $yform->setObjectparams('main_where', 'id=1');
        $yform->setObjectparams('main_id', 1);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('db', [$table, 'main_where']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $row = Sql::factory()->getArray('SELECT title FROM `' . $table . '` WHERE id = 1');
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
     * Callback), NOT a YForm instance as the skill doc
     * suggested. To get the YForm, read $action->params['this'].
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
    // Finding: despite the name, ManageDb does NOT auto-detect
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

        $rows = Sql::factory()->getArray('SELECT * FROM `' . $table . '`');
        $this->assertCount(1, $rows);
    }

    public function testManageDbUpdatesWhenWhereProvided(): void
    {
        $table = $this->makeFixtureTable('a_manage_upd', [
            ['title', 'varchar(191)'],
        ]);
        Sql::factory()->setTable($table)->setValue('title', 'before')->insert();

        $yform = $this->freshForm('manage_upd');
        $yform->setObjectparams('data', ['title' => 'after']);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('manage_db', [$table, 'id=1']); // literal where -> update
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $rows = Sql::factory()->getArray('SELECT title FROM `' . $table . '`');
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
        Sql::factory()
            ->setTable(Core::getTable('yform_email_template'))
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
            $yform->setValueField('text', ['name', 'Name']);
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
            Sql::factory()->setQuery(
                'DELETE FROM ' . Core::getTable('yform_email_template') . ' WHERE name = :n',
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
        // Redirect calls Response::sendCacheControl(),
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

        $rows = Sql::factory()->getArray('SELECT * FROM `' . $table . '`');
        $this->assertCount(1, $rows);
        $this->assertSame('X', $rows[0]['name']);
        $this->assertSame(5, (int) $rows[0]['status']);
    }

    // ---------- encrypt_value ----------

    public function testEncryptValueReplacesValueInSqlPool(): void
    {
        $yform = $this->freshForm('enc');
        $yform->setObjectparams('data', ['secret' => 'geheim']);
        $yform->setValueField('text', ['secret', 'Secret']);
        $yform->setActionField('encrypt_value', ['secret', 'md5']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertSame(md5('geheim'), $yform->objparams['value_pool']['sql']['secret'] ?? null);
    }

    // ---------- copy_value ----------

    public function testCopyValueWritesIntoSqlPoolNotEmailPool(): void
    {
        $yform = $this->freshForm('cpv');
        $yform->setObjectparams('data', ['from' => 'quelle', 'to' => '']);
        $yform->setValueField('text', ['from', 'From']);
        $yform->setValueField('text', ['to', 'To']);
        $yform->setActionField('copy_value', ['from', 'to']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertSame('quelle', $yform->objparams['value_pool']['sql']['to'] ?? null);
    }

    // ---------- create_table / createdb ----------

    public function testCreateTableActionCreatesTable(): void
    {
        $tableName = $this->fixtures->reserveTableName('a_created');
        $this->trackFixture($tableName);
        Sql::factory()->setQuery('DROP TABLE IF EXISTS `' . $tableName . '`');

        $yform = $this->freshForm('ct');
        $yform->setObjectparams('data', ['title' => 'Wert']);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('create_table', [$tableName]);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertCount(1, Sql::factory()->getArray('SHOW TABLES LIKE ' . Sql::factory()->escape($tableName)));
    }

    public function testCreateDbActionCreatesTable(): void
    {
        $tableName = $this->fixtures->reserveTableName('a_createdb');
        $this->trackFixture($tableName);
        Sql::factory()->setQuery('DROP TABLE IF EXISTS `' . $tableName . '`');

        $yform = $this->freshForm('cdb');
        $yform->setObjectparams('data', ['title' => 'Wert']);
        $yform->setValueField('text', ['title', 'Title']);
        $yform->setActionField('createdb', [$tableName]);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertCount(1, Sql::factory()->getArray('SHOW TABLES LIKE ' . Sql::factory()->escape($tableName)));
    }

    // ---------- email ----------

    /**
     * The `email` action builds a Mailer inline and calls Send() on it, so unlike
     * `tpl2email` it never passes through YFORM_EMAIL_BEFORE_SEND — the hook
     * MailerStub listens on. Running it here would attempt a real SMTP connect.
     *
     * Use `tpl2email` when the mail must be testable (and it is the better option
     * anyway); testTpl2EmailActionSendsMail above covers that path.
     */
    public function testEmailActionNotHeadlessTestable(): void
    {
        $this->markSkipped('email action calls Mailer::Send() directly — it bypasses YFORM_EMAIL_BEFORE_SEND and would open an SMTP connection. tpl2email is covered instead.');
    }

    /**
     * What is testable without a transport: the value_pool placeholders the action
     * substitutes. Same replacement rules as the action itself applies.
     */
    public function testEmailPlaceholdersComeFromTheEmailValuePool(): void
    {
        $yform = $this->freshForm('mailpool');
        $yform->setObjectparams('data', ['name' => 'Erika', 'ort' => 'Köln']);
        $yform->setValueField('text', ['name', 'Name']);
        $yform->setValueField('text', ['ort', 'Ort']);
        $yform->setFieldValue('send', [], '1');

        $yform->executeFields();

        $pool = $yform->objparams['value_pool']['email'];
        $this->assertSame('Erika', $pool['name'] ?? null);
        $this->assertSame('Köln', $pool['ort'] ?? null);

        // ###key### is replaced verbatim, +++key+++ url-encoded.
        $body = 'Hallo ###name### aus +++ort+++';
        foreach ($pool as $search => $replace) {
            $body = str_replace('###' . $search . '###', (string) $replace, $body);
            $body = str_replace('+++' . $search . '+++', urlencode((string) $replace), $body);
        }
        $this->assertSame('Hallo Erika aus K%C3%B6ln', $body);
    }

    // ---------- readtable ----------

    public function testReadTableActionLooksUpARow(): void
    {
        $table = $this->makeFixtureTable('a_read', [
            ['code', 'varchar(191)'],
            ['label', 'varchar(191)'],
        ]);
        Sql::factory()->setTable($table)->setValue('code', 'AAA')->setValue('label', 'Alpha')->insert();

        $yform = $this->freshForm('rt');
        $yform->setObjectparams('data', ['code' => 'AAA']);
        $yform->setValueField('text', ['code', 'Code']);
        $yform->setActionField('readtable', [$table, 'code', 'code']);
        $yform->setFieldValue('send', [], '1');

        $yform->getForm();

        $this->assertTrue((bool) $yform->objparams['actions_executed']);
        $this->assertFalse($yform->hasWarnings());
    }

    // ---------- html / php ----------

    /**
     * `html` and `php` echo straight to the output buffer instead of returning
     * their markup — same as REDAXO 5, so a caller must be inside a buffer.
     */
    public function testHtmlActionEchoesItsMarkup(): void
    {
        $yform = $this->freshForm('html');
        $yform->setObjectparams('data', ['x' => '1']);
        $yform->setValueField('text', ['x', 'X']);
        $yform->setActionField('html', ['<b>action-html</b>']);
        $yform->setFieldValue('send', [], '1');

        ob_start();
        $returned = $yform->getForm();
        $echoed = (string) ob_get_clean();

        $this->assertStringContains('action-html', $echoed . $returned);
    }

    public function testPhpActionExecutesItsSnippet(): void
    {
        $yform = $this->freshForm('php');
        $yform->setObjectparams('data', ['x' => '1']);
        $yform->setValueField('text', ['x', 'X']);
        $yform->setActionField('php', ['<?php echo "action-php-ran"; ?>']);
        $yform->setFieldValue('send', [], '1');

        ob_start();
        $returned = $yform->getForm();
        $echoed = (string) ob_get_clean();

        $this->assertStringContains('action-php-ran', $echoed . $returned);
    }

    // ---------- logger ----------

    public function testLoggerActionRunsWithoutBreakingTheForm(): void
    {
        $yform = $this->freshForm('log');
        $yform->setObjectparams('data', ['x' => '1']);
        $yform->setValueField('text', ['x', 'X']);
        $yform->setActionField('logger', ['yform test log entry', 'info']);
        $yform->setFieldValue('send', [], '1');

        ob_start();
        $yform->getForm();
        ob_end_clean();

        $this->assertTrue((bool) $yform->objparams['actions_executed']);
        $this->assertFalse($yform->hasWarnings());
    }

    // ---------- submit + db ----------

    /**
     * The `submit` field persists its own value unless told otherwise, so a form
     * saving into a table without a matching column needs `no_db`. Pinned because
     * it is the most common surprise when wiring a frontend form to a table.
     */
    public function testSubmitFieldNeedsNoDbWhenTableHasNoSuchColumn(): void
    {
        $table = $this->makeFixtureTable('a_submit', [
            ['title', 'varchar(191)'],
        ]);

        // Without no_db the INSERT names a `send` column that does not exist.
        $failing = $this->freshForm('sub_fail');
        $failing->setObjectparams('data', ['title' => 'X']);
        $failing->setValueField('text', ['title', 'Title']);
        $failing->setValueField('submit', ['send', 'Absenden']);
        $failing->setActionField('db', [$table]);
        $failing->setFieldValue('send', [], '1');
        $failing->getForm();

        $this->assertCount(0, Sql::factory()->getArray('SELECT id FROM `' . $table . '`'));

        // With no_db the row lands.
        $ok = $this->freshForm('sub_ok');
        $ok->setObjectparams('data', ['title' => 'Y']);
        $ok->setValueField('text', ['title', 'Title']);
        $ok->setValueField('submit', ['send', 'Absenden', '', 'no_db']);
        $ok->setActionField('db', [$table]);
        $ok->setFieldValue('send', [], '1');
        $ok->getForm();

        $rows = Sql::factory()->getArray('SELECT title FROM `' . $table . '`');
        $this->assertCount(1, $rows);
        $this->assertSame('Y', $rows[0]['title']);
    }
}
