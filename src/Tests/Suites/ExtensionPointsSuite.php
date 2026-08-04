<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\ExtensionPoint\ExtensionLevel;
use Redaxo\Core\Core;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Manager\Dataset;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;
use Yakamara\YForm\YForm;

use function count;
use function in_array;

/**
 * Tests for YForm's extension points (EPs).
 * Covers §3.I.4 from .claude/plans/02-test-strategy.md.
 *
 * Strategy: `Extension` has no `unregister`. We install probe handlers
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
     * @var array<string, list<array{subject: mixed, params: array<string, mixed>}>>
     */
    public static array $epLog = [];

    /** One-shot veto flag for testYformDataDeleteCanCancel. */
    public static bool $vetoNextDelete = false;

    private static bool $handlersInstalled = false;

    private string $tableName = '';

    public function setUpBeforeClass(): void
    {
        $this->tableName = $this->fixtures->reserveTableName('ep_main');

        try {
            Api::removeTable($this->tableName);
        } catch (Throwable) {
        }
        try {
            DbTable::get($this->tableName)->drop();
        } catch (Throwable) {
        }

        DbTable::get($this->tableName)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('title', 'varchar(191)', true))
            ->ensureColumn(new Column('status', 'int(11)', true))
            ->ensure();

        Api::setTable([
            'table_name' => $this->tableName,
            'name' => 'ep_main',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text',    'name' => 'title',  'label' => 'T', 'prio' => 1],
            ['type_id' => 'value', 'type_name' => 'integer', 'name' => 'status', 'label' => 'S', 'prio' => 2],
        ]);
        $this->trackFixture($this->tableName);
        Table::deleteCache();

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
            Extension::register($name, static function (ExtensionPoint $ep) use ($name): mixed {
                self::$epLog[$name][] = [
                    'subject' => $ep->subject,
                    'params' => $ep->getParams(),
                ];
                return $ep->subject;
            });
        }

        // YFORM_DATA_DELETE is special — cancellable. We install a guarded veto
        // handler that only fires when explicitly armed via $vetoNextDelete.
        Extension::register('YFORM_DATA_DELETE', static function (ExtensionPoint $ep): mixed {
            self::$epLog['YFORM_DATA_DELETE'][] = [
                'subject' => $ep->subject,
                'params' => $ep->getParams(),
            ];
            if (self::$vetoNextDelete) {
                self::$vetoNextDelete = false; // one-shot
                return false;
            }
            return $ep->subject;
        }, ExtensionLevel::Early);
    }

    public function setUp(): void
    {
        self::$epLog = [];
        self::$vetoNextDelete = false;
        Sql::factory()->setQuery('TRUNCATE `' . $this->tableName . '`');
    }

    /**
     * Typed accessor — PHPStan ignores the @var on $epLog because of the
     * inline `= []` initializer, so we expose a typed helper instead.
     *
     * @return list<array{subject: mixed, params: array<string, mixed>}>
     */
    private static function log(string $ep): array
    {
        /** @phpstan-ignore-next-line */
        return self::$epLog[$ep] ?? [];
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
        new YForm();
        $this->assertTrue(count(self::log('YFORM_INIT')) >= 1);
        $this->assertInstanceOf(YForm::class, self::log('YFORM_INIT')[0]['subject']);
    }

    // ---------- YFORM_DATA_ADD / ADDED ----------

    public function testYformDataAddAndAddedFireOnCreate(): void
    {
        $ds = Dataset::create($this->tableName);
        $ds->setValue('title', 'add-fires');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());

        // ADD fires before save with the YForm; ADDED fires after.
        $this->assertTrue(count(self::log('YFORM_DATA_ADD')) >= 1);
        $this->assertTrue(count(self::log('YFORM_DATA_ADDED')) >= 1);

        $added = self::log('YFORM_DATA_ADDED')[0];
        $this->assertSame($this->tableName, $added['params']['table']->getTableName());
        $this->assertTrue(($added['params']['data_id'] ?? 0) > 0);
    }

    // ---------- YFORM_DATA_UPDATE / UPDATED ----------

    public function testYformDataUpdateAndUpdatedFireOnUpdate(): void
    {
        $ds = Dataset::create($this->tableName);
        $ds->setValue('title', 'before');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());

        // Reset log so the create's events don't pollute the update assertions.
        self::$epLog = [];

        $ds->setValue('title', 'after');
        $this->assertTrue($ds->save());

        $this->assertTrue(count(self::log('YFORM_DATA_UPDATE')) >= 1);
        $this->assertTrue(count(self::log('YFORM_DATA_UPDATED')) >= 1);

        // Finding: YFORM_DATA_UPDATED's 'old_data' param is misleadingly named.
        // dataset->executeForm() captures it as `$this->getData()` right before
        // running the form pipeline — but setValue() has ALREADY mutated $this->data
        // by then. So 'old_data' is the in-memory state AT-SAVE-TIME, not the
        // pre-update DB state. To get the real "old" state, a handler would need
        // to load a fresh dataset from DB inside YFORM_DATA_UPDATE.
        $updated = self::log('YFORM_DATA_UPDATED')[0];
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
        $ds = Dataset::create($this->tableName);
        $ds->setValue('title', 'doomed');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        self::$epLog = [];

        $this->assertTrue($ds->delete());

        $this->assertTrue(count(self::log('YFORM_DATA_DELETE')) >= 1);
        $this->assertTrue(count(self::log('YFORM_DATA_DELETED')) >= 1);
    }

    public function testYformDataDeleteCanCancel(): void
    {
        $ds = Dataset::create($this->tableName);
        $ds->setValue('title', 'should-survive');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        $id = $ds->getId();
        self::$epLog = [];

        self::$vetoNextDelete = true;
        $this->assertFalse($ds->delete(), 'A YFORM_DATA_DELETE handler returning false must veto the delete.');

        // DELETE fired (probe + veto handler both ran), DELETED did NOT fire.
        $this->assertTrue(count(self::log('YFORM_DATA_DELETE')) >= 1);
        $this->assertSame(0, count(self::log('YFORM_DATA_DELETED')));

        // Row should still exist.
        Dataset::clearInstance([$this->tableName, $id]);
        $stillThere = Dataset::get($id, $this->tableName);
        $this->assertNotNull($stillThere);
    }

    // ---------- YFORM_SAVED ----------

    public function testYformSavedFiresInsideDbActionWithRexSqlSubject(): void
    {
        $ds = Dataset::create($this->tableName);
        $ds->setValue('title', 'saved-probe');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());

        $this->assertTrue(count(self::log('YFORM_SAVED')) >= 1);
        $saved = self::log('YFORM_SAVED')[0];
        $this->assertInstanceOf(Sql::class, $saved['subject']);
        $this->assertSame($this->tableName, $saved['params']['table']);
        $this->assertSame('insert', $saved['params']['action']);
        $this->assertArrayHasKey('id', $saved['params']);
    }

    public function testYformSavedActionParamReadsUpdateOnSecondSave(): void
    {
        $ds = Dataset::create($this->tableName);
        $ds->setValue('title', 'first');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save());
        self::$epLog = [];

        $ds->setValue('title', 'second');
        $this->assertTrue($ds->save());

        $this->assertTrue(count(self::log('YFORM_SAVED')) >= 1);
        $this->assertSame('update', self::log('YFORM_SAVED')[0]['params']['action']);
    }

    // ---------- YFORM_EMAIL_BEFORE_SEND ----------

    public function testYformEmailBeforeSendFiresViaTpl2Email(): void
    {
        // Note: MailerStub is also a YFORM_EMAIL_BEFORE_SEND handler that sets
        // status=true to short-circuit. Order is non-deterministic across
        // handlers — but both fire, and our probe records the call.
        $tplName = 'unittest_ep_tpl_' . substr(uniqid(), -6);
        Sql::factory()
            ->setTable(Core::getTable('yform_email_template'))
            ->setValue('name', $tplName)
            ->setValue('mail_from', 'a@example.com')
            ->setValue('mail_from_name', 'A')
            ->setValue('subject', 'Hi')
            ->setValue('body', 'Body')
            ->setValue('body_html', '')
            ->setValue('attachments', '')
            ->insert();

        try {
            $yform = new YForm();
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

            $this->assertTrue(count(self::log('YFORM_EMAIL_BEFORE_SEND')) >= 1);
        } finally {
            Sql::factory()->setQuery(
                'DELETE FROM ' . Core::getTable('yform_email_template') . ' WHERE name = :n',
                [':n' => $tplName],
            );
        }
    }

    // ---------- Cancellation semantics summary ----------

    public function testNonCancellableEpsPassSubjectThrough(): void
    {
        // YFORM_DATA_ADDED is NOT cancellable — its subject is the YForm;
        // handlers that return non-yform values don't break dataset save flow.
        // Our probe returns $ep->subject unchanged, so this also verifies
        // that the probe doesn't accidentally cancel things.
        $ds = Dataset::create($this->tableName);
        $ds->setValue('title', 'pass-through');
        $ds->setValue('status', 1);
        $this->assertTrue($ds->save(), 'Probe handlers must not break save.');
    }
}
