<?php

declare(strict_types=1);

namespace Redaxo\YForm\Tests\Suites;

use Redaxo\YForm\Test\AbstractTestSuite;
use ReflectionClass;
use rex;
use rex_extension;
use rex_extension_point;
use rex_sql;
use rex_sql_column;
use rex_sql_table;
use rex_yform;
use rex_yform_manager_dataset;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use Throwable;

use function count;
use function in_array;

/**
 * Tests for YForm's extension points (EPs).
 * Covers §3.I.4 from .claude/plans/02-test-strategy.md.
 *
 * Strategy: `rex_extension` has no `unregister`. We install probe handlers
 * once per suite run in setUpBeforeClass() and record every invocation into
 * the static $epLog. Tests reset $epLog in setUp() and then trigger an EP
 * by performing the corresponding dataset action.
 *
 * For cancellable EPs (YFORM_DATA_DELETE), a flag-driven handler is registered
 * once and only fires its veto when a specific test sets the flag.
 *
 * @package redaxo\yform
 * @internal
 */
final class ExtensionPointsSuite extends AbstractTestSuite
{
    /**
     * Log of EP invocations.
     * Shape: ['YFORM_DATA_ADD' => [ ['subject' => ..., 'params' => ...], ... ], ...].
     *
     * @var array<string, list<array{subject: mixed, params: array}>>
     */
    /** @var array<string, list<array{subject: mixed, params: array<string, mixed>}>> */
    public static array $epLog = [];

    /** One-shot veto flag for testYformDataDeleteCanCancel. */
    public static bool $vetoNextDelete = false;

    private static bool $handlersInstalled = false;

    private string $tableName = '';

    public function setUpBeforeClass(): void
    {
        $this->tableName = $this->fixtures->reserveTableName('ep_main');

        try {
            rex_yform_manager_table_api::removeTable($this->tableName);
        } catch (Throwable) {
        }
        try {
            rex_sql_table::get($this->tableName)->drop();
        } catch (Throwable) {
        }

        rex_sql_table::get($this->tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new rex_sql_column('title', 'varchar(191)', true))
            ->ensureColumn(new rex_sql_column('status', 'int(11)', true))
            ->ensure();

        rex_yform_manager_table_api::setTable([
            'table_name' => $this->tableName,
            'name' => 'ep_main',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text',    'name' => 'title',  'label' => 'T', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'status', 'label' => 'S', 'prio' => 2],
        ]);
        $this->trackFixture($this->tableName);
        rex_yform_manager_table::deleteCache();

        if (self::$handlersInstalled) {
            return;
        }
        self::$handlersInstalled = true;

        $epNames = [
            'YFORM_INIT',
            'YFORM_GENERATE',
            'YFORM_EXECUTE_FIELDS',
            'YFORM_SAVED',
            'YFORM_DATA_ADD',
            'YFORM_DATA_ADDED',
            'YFORM_DATA_UPDATE',
            'YFORM_DATA_UPDATED',
            'YFORM_DATA_DELETED',
            'YFORM_EMAIL_BEFORE_SEND',
        ];
        foreach ($epNames as $name) {
            rex_extension::register($name, static function (rex_extension_point $ep) use ($name): mixed {
                self::$epLog[$name][] = [
                    'subject' => $ep->getSubject(),
                    'params' => $ep->getParams(),
                ];
                return $ep->getSubject();
            });
        }

        // YFORM_DATA_DELETE is special — cancellable. We install a guarded veto
        // handler that only fires when explicitly armed via $vetoNextDelete.
        rex_extension::register('YFORM_DATA_DELETE', static function (rex_extension_point $ep): mixed {
            self::$epLog['YFORM_DATA_DELETE'][] = [
                'subject' => $ep->getSubject(),
                'params' => $ep->getParams(),
            ];
            if (self::$vetoNextDelete) {
                self::$vetoNextDelete = false; // one-shot
                return false;
            }
            return $ep->getSubject();
        }, rex_extension::EARLY);
    }

    public function setUp(): void
    {
        self::$epLog = [];
        self::$vetoNextDelete = false;
        rex_sql::factory()->setQuery('TRUNCATE `' . $this->tableName . '`');
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

    // ---------- YFORM_INIT ----------

    public function testYformInitFiresOnConstruct(): void
    {
        new rex_yform();
        $this->assertTrue(count(self::$epLog['YFORM_INIT'] ?? []) >= 1);
        $this->assertInstanceOf(rex_yform::class, self::$epLog['YFORM_INIT'][0]['subject']);
    }

    // ---------- YFORM_DATA_ADD / ADDED ----------

    public function testYformDataAddAndAddedFireOnCreate(): void
    {
        $ds = rex_yform_manager_dataset::create($this->tableName);
        $ds->setValue('title', 'add-fires');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());

        // ADD fires before save with the rex_yform; ADDED fires after.
        $this->assertTrue(count(self::$epLog['YFORM_DATA_ADD'] ?? []) >= 1);
        $this->assertTrue(count(self::$epLog['YFORM_DATA_ADDED'] ?? []) >= 1);

        $added = self::$epLog['YFORM_DATA_ADDED'][0];
        $this->assertSame($this->tableName, $added['params']['table']->getTableName());
        $this->assertTrue(($added['params']['data_id'] ?? 0) > 0);
    }

    // ---------- YFORM_DATA_UPDATE / UPDATED ----------

    public function testYformDataUpdateAndUpdatedFireOnUpdate(): void
    {
        $ds = rex_yform_manager_dataset::create($this->tableName);
        $ds->setValue('title', 'before');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());

        // Reset log so the create's events don't pollute the update assertions.
        self::$epLog = [];

        $ds->setValue('title', 'after');
        $this->assertTrue($ds->save());

        $this->assertTrue(count(self::$epLog['YFORM_DATA_UPDATE'] ?? []) >= 1);
        $this->assertTrue(count(self::$epLog['YFORM_DATA_UPDATED'] ?? []) >= 1);

        // Finding: YFORM_DATA_UPDATED's 'old_data' param is misleadingly named.
        // dataset->executeForm() captures it as `$this->getData()` right before
        // running the form pipeline — but setValue() has ALREADY mutated $this->data
        // by then. So 'old_data' is the in-memory state AT-SAVE-TIME, not the
        // pre-update DB state. To get the real "old" state, a handler would need
        // to load a fresh dataset from DB inside YFORM_DATA_UPDATE.
        $updated = self::$epLog['YFORM_DATA_UPDATED'][0];
        $this->assertArrayHasKey('old_data', $updated['params']);
        $this->assertSame(
            'after',
            $updated['params']['old_data']['title'] ?? null,
            'old_data reflects post-setValue / pre-SQL state — see comment.',
        );
    }

    // ---------- YFORM_DATA_DELETE / DELETED ----------

    public function testYformDataDeleteFiresAndDeletedFollowsOnSuccess(): void
    {
        $ds = rex_yform_manager_dataset::create($this->tableName);
        $ds->setValue('title', 'doomed');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        self::$epLog = [];

        $this->assertTrue($ds->delete());

        $this->assertTrue(count(self::$epLog['YFORM_DATA_DELETE'] ?? []) >= 1);
        $this->assertTrue(count(self::$epLog['YFORM_DATA_DELETED'] ?? []) >= 1);
    }

    public function testYformDataDeleteCanCancel(): void
    {
        $ds = rex_yform_manager_dataset::create($this->tableName);
        $ds->setValue('title', 'should-survive');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        $id = $ds->getId();
        self::$epLog = [];

        self::$vetoNextDelete = true;
        $this->assertFalse($ds->delete(), 'A YFORM_DATA_DELETE handler returning false must veto the delete.');

        // DELETE fired (probe + veto handler both ran), DELETED did NOT fire.
        $this->assertTrue(count(self::$epLog['YFORM_DATA_DELETE'] ?? []) >= 1);
        $this->assertSame(0, count(self::$epLog['YFORM_DATA_DELETED'] ?? []));

        // Row should still exist.
        rex_yform_manager_dataset::clearInstance([$this->tableName, $id]);
        $stillThere = rex_yform_manager_dataset::get($id, $this->tableName);
        $this->assertNotNull($stillThere);
    }

    // ---------- YFORM_SAVED ----------

    public function testYformSavedFiresInsideDbActionWithRexSqlSubject(): void
    {
        $ds = rex_yform_manager_dataset::create($this->tableName);
        $ds->setValue('title', 'saved-probe');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());

        $this->assertTrue(count(self::$epLog['YFORM_SAVED'] ?? []) >= 1);
        $saved = self::$epLog['YFORM_SAVED'][0];
        $this->assertInstanceOf(rex_sql::class, $saved['subject']);
        $this->assertSame($this->tableName, $saved['params']['table']);
        $this->assertSame('insert', $saved['params']['action']);
        $this->assertArrayHasKey('id', $saved['params']);
    }

    public function testYformSavedActionParamReadsUpdateOnSecondSave(): void
    {
        $ds = rex_yform_manager_dataset::create($this->tableName);
        $ds->setValue('title', 'first');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        self::$epLog = [];

        $ds->setValue('title', 'second');
        $this->assertTrue($ds->save());

        $this->assertTrue(count(self::$epLog['YFORM_SAVED'] ?? []) >= 1);
        $this->assertSame('update', self::$epLog['YFORM_SAVED'][0]['params']['action']);
    }

    // ---------- YFORM_EMAIL_BEFORE_SEND ----------

    public function testYformEmailBeforeSendFiresViaTpl2Email(): void
    {
        // Note: MailerStub is also a YFORM_EMAIL_BEFORE_SEND handler that sets
        // status=true to short-circuit. Order is non-deterministic across
        // handlers — but both fire, and our probe records the call.
        $tplName = 'unittest_ep_tpl_' . substr(uniqid(), -6);
        rex_sql::factory()
            ->setTable(rex::getTable('yform_email_template'))
            ->setValue('name', $tplName)
            ->setValue('mail_from', 'a@example.com')
            ->setValue('mail_from_name', 'A')
            ->setValue('subject', 'Hi')
            ->setValue('body', 'Body')
            ->setValue('body_html', '')
            ->setValue('attachments', '')
            ->insert();

        try {
            $yform = new rex_yform();
            $yform->setObjectparams('form_name', 'ep_email_' . substr(uniqid(), -6));
            $yform->setObjectparams('real_field_names', true);
            $yform->setObjectparams('form_needs_output', false);
            $yform->setObjectparams('csrf_protection', false);
            $yform->setObjectparams('form_exit', false);
            $yform->setObjectparams('data', ['email' => 'r@example.com']);
            $yform->setValueField('email', ['email', 'Email']);
            $yform->setActionField('tpl2email', [$tplName, 'email']);
            $yform->setFieldValue('send', [], '1');
            $yform->getForm();

            $this->assertTrue(count(self::$epLog['YFORM_EMAIL_BEFORE_SEND'] ?? []) >= 1);
        } finally {
            rex_sql::factory()->setQuery(
                'DELETE FROM ' . rex::getTable('yform_email_template') . ' WHERE name = :n',
                [':n' => $tplName],
            );
        }
    }

    // ---------- Cancellation semantics summary ----------

    public function testNonCancellableEpsPassSubjectThrough(): void
    {
        // YFORM_DATA_ADDED is NOT cancellable — its subject is the rex_yform;
        // handlers that return non-yform values don't break dataset save flow.
        // Our probe returns $ep->getSubject() unchanged, so this also verifies
        // that the probe doesn't accidentally cancel things.
        $ds = rex_yform_manager_dataset::create($this->tableName);
        $ds->setValue('title', 'pass-through');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save(), 'Probe handlers must not break save.');
    }
}
