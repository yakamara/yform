<?php

declare(strict_types=1);

namespace Yakamara\YForm\Tests\Suites;

use Redaxo\Core\Core;
use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Database\Table as DbTable;
use Redaxo\Core\Security\User;
use Redaxo\Core\Security\UserRole;
use ReflectionClass;
use Throwable;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Authorization;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\Test\AbstractTestSuite;

use function in_array;

/**
 * Tests for Authorization.
 * Covers §3.U.8 from .claude/plans/02-test-strategy.md.
 *
 * Strategy: build minimal real User instances via direct SQL inserts into
 * User, then drive isGranted() / onAttribute() through them. Cleanup removes
 * the inserted user rows.
 *
 * Important: Authorization::$tableAuthorizations is a
 * static cache that is keyed per user and never auto-invalidated.
 * Every test resets it manually to start from a clean state.
 *
 * @package redaxo\yform
 * @internal
 */
final class AuthorizationSuite extends AbstractTestSuite
{
    /** Login names of users created during the suite — cleaned up in tearDownAfterClass. */
    private static array $userLogins = [];
    /** @var list<string> */
    private static array $roleNames = [];

    private string $tableA = '';
    private string $tableB = '';

    public function setUpBeforeClass(): void
    {
        $this->tableA = $this->fixtures->reserveTableName('auth_a');
        $this->tableB = $this->fixtures->reserveTableName('auth_b');

        foreach ([$this->tableA, $this->tableB] as $t) {
            try {
                Api::removeTable($t);
            } catch (Throwable) {
            }
            try {
                DbTable::get($t)->drop();
            } catch (Throwable) {
            }
        }

        DbTable::get($this->tableA)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('name', 'varchar(191)', true))
            ->ensureColumn(new Column('related_id', 'int(11)', true))
            ->ensure();

        DbTable::get($this->tableB)
            ->ensurePrimaryIdColumn()
            ->ensureColumn(new Column('name', 'varchar(191)', true))
            ->ensure();

        // Table B has no relations.
        Api::setTable([
            'table_name' => $this->tableB,
            'name' => 'auth_b',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'name', 'label' => 'N', 'prio' => 1],
        ]);

        // Table A has a be_manager_relation pointing at B.
        Api::setTable([
            'table_name' => $this->tableA,
            'name' => 'auth_a',
            'status' => 1,
            'hidden' => 1,
        ], [
            ['type_id' => 'value', 'type_name' => 'text', 'name' => 'name', 'label' => 'N', 'prio' => 1],
            [
                'type_id' => 'value',
                'type_name' => 'be_manager_relation',
                'name' => 'related_id',
                'label' => 'Related',
                'table' => $this->tableB,
                'field' => 'name',
                'type' => 0,
                'empty_option' => 1,
                'prio' => 2,
            ],
        ]);

        $this->trackFixture($this->tableA);
        $this->trackFixture($this->tableB);
        Table::deleteCache();
    }

    public function tearDownAfterClass(): void
    {
        foreach (self::$userLogins as $login) {
            Sql::factory()->setQuery('DELETE FROM ' . Core::getTable('user') . ' WHERE login = :l', [':l' => $login]);
        }
        self::$userLogins = [];

        foreach (self::$roleNames as $name) {
            Sql::factory()->setQuery('DELETE FROM ' . Core::getTable('user_role') . ' WHERE name = :n', [':n' => $name]);
        }
        self::$roleNames = [];
    }

    public function setUp(): void
    {
        // Reset the static auth cache so each test sees fresh evaluation.
        Authorization::$tableAuthorizations = null;
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

    /**
     * Creates a real User row for the test, returns the loaded User.
     *
     * @param array<string, string> $complexPerms complex-perm key => pipe-delimited item list
     */
    private function makeUser(bool $admin, array $complexPerms = []): User
    {
        $login = 'unittest_auth_' . substr(uniqid('', true), -8);
        self::$userLogins[] = $login;

        $roleId = '';
        if ([] !== $complexPerms) {
            $roleId = (string) $this->makeRole($complexPerms);
        }

        $sql = Sql::factory();
        $sql->setTable(Core::getTable('user'));
        $sql->setValue('login', $login);
        $sql->setValue('name', 'Auth Test');
        $sql->setValue('email', $login . '@example.com');
        $sql->setValue('admin', $admin ? 1 : 0);
        $sql->setValue('role', $roleId);
        $sql->setValue('status', 1);
        $sql->setValue('createdate', $sql::datetime());
        $sql->setValue('updatedate', $sql::datetime());
        $sql->setValue('createuser', 'unittest');
        $sql->setValue('updateuser', 'unittest');
        $sql->setValue('password', password_hash('not-used', PASSWORD_BCRYPT));
        $sql->setValue('password_changed', $sql::datetime());
        $sql->setValue('previous_passwords', '');
        $sql->setValue('password_change_required', 0);
        $sql->setValue('login_tries', 0);
        $sql->insert();
        $id = (int) $sql->getLastId();

        return User::require($id);
    }

    /**
     * Creates a user role granting the given complex permissions.
     *
     * The shape matters: rex_user_role.perms is a JSON object whose complex-perm values
     * are **pipe-delimited strings** ("|table_a|table_b|"), because UserRole runs
     * explode('|', trim($value, '|')) over them. An array would collapse to nothing.
     *
     * @param array<string, string> $complexPerms
     */
    private function makeRole(array $complexPerms): int
    {
        $name = 'unittest_auth_role_' . substr(uniqid('', true), -8);
        self::$roleNames[] = $name;

        $perms = json_encode(array_merge([
            'general' => '',
            'options' => '',
            'extras' => '',
        ], $complexPerms), JSON_THROW_ON_ERROR);

        $sql = Sql::factory();
        $sql->setTable(Core::getTable('user_role'));
        $sql->setValue('name', $name);
        $sql->setValue('description', 'created by AuthorizationSuite');
        $sql->setValue('perms', $perms);
        $sql->setValue('createdate', $sql::datetime());
        $sql->setValue('updatedate', $sql::datetime());
        $sql->setValue('createuser', 'unittest');
        $sql->setValue('updateuser', 'unittest');
        $sql->insert();

        return (int) $sql->getLastId();
    }

    // ---------- null user ----------

    public function testNullUserHasNeitherViewNorEdit(): void
    {
        $a = Table::require($this->tableA);
        $this->assertFalse(Authorization::onAttribute('VIEW', $a, null));
        $this->assertFalse(Authorization::onAttribute('EDIT', $a, null));
    }

    // ---------- admin user ----------

    public function testAdminUserHasBothViewAndEdit(): void
    {
        $admin = $this->makeUser(true);

        $a = Table::require($this->tableA);
        $b = Table::require($this->tableB);

        $this->assertTrue(Authorization::onAttribute('VIEW', $a, $admin));
        $this->assertTrue(Authorization::onAttribute('EDIT', $a, $admin));
        // Cache was populated on first call — second call uses cached state.
        // We can still query other tables: the cache covers all tables.
        $this->assertTrue(Authorization::onAttribute('EDIT', $b, $admin));
    }

    public function testIsGrantedHelperRoutesThroughAuthorization(): void
    {
        $admin = $this->makeUser(true);
        $a = Table::require($this->tableA);

        // Table::isGranted() delegates to the authorization class.
        $this->assertTrue($a->isGranted('VIEW', $admin));
        $this->assertTrue($a->isGranted('EDIT', $admin));
    }

    // ---------- non-admin without role ----------

    public function testNonAdminWithoutRoleHasNothing(): void
    {
        $u = $this->makeUser(false);
        $a = Table::require($this->tableA);

        $this->assertFalse(Authorization::onAttribute('VIEW', $a, $u));
        $this->assertFalse(Authorization::onAttribute('EDIT', $a, $u));
    }

    // ---------- cache semantics ----------

    public function testTableAuthorizationsCacheIsKeyedPerUser(): void
    {
        $admin = $this->makeUser(true);
        $a = Table::require($this->tableA);

        // First call: populates the cache for the admin user.
        $this->assertTrue(Authorization::onAttribute('VIEW', $a, $admin));
        $this->assertNotNull(Authorization::$tableAuthorizations);
        $this->assertArrayHasKey((int) $admin->id, Authorization::$tableAuthorizations);

        // Second call WITH NO USER: must NOT return the admin's cached state.
        // Pre-fix this returned true. Now it correctly evaluates as guest.
        $this->assertFalse(
            Authorization::onAttribute('VIEW', $a, null),
            'Per-user cache: null user must be evaluated independently of admin.',
        );

        // Both entries co-exist in the cache.
        $this->assertArrayHasKey((int) $admin->id, Authorization::$tableAuthorizations);
        $this->assertArrayHasKey('guest', Authorization::$tableAuthorizations);

        // Explicit reset clears everything.
        Authorization::$tableAuthorizations = null;
        $this->assertNull(Authorization::$tableAuthorizations);
    }

    // ---------- relations carry VIEW ----------

    public function testRelatedTableInheritsViewFromSourceWhenAdminCanSeeSource(): void
    {
        $admin = $this->makeUser(true);
        $a = Table::require($this->tableA);
        $b = Table::require($this->tableB);

        // With admin, both tables get VIEW + EDIT explicitly. The relation-carry
        // logic kicks in when a table has VIEW but the related table doesn't get
        // it on its own — for admin, this is moot, so we just verify both have
        // VIEW.
        $this->assertTrue(Authorization::onAttribute('VIEW', $a, $admin));
        $this->assertTrue(Authorization::onAttribute('VIEW', $b, $admin));
    }

    // ---------- non-admin with a role ----------

    /**
     * The per-table grant path. `rex_user_role.perms` is a JSON object whose complex-perm
     * entries are **pipe-delimited strings**, not arrays — UserRole::__construct() runs
     * explode('|', trim($value, '|')) over them. An earlier version of this suite guessed
     * `{"yform_manager_table_view":["rex_table"]}` and skipped instead of asserting.
     */
    public function testNonAdminWithRoleIsGrantedTheListedTable(): void
    {
        $user = $this->makeUser(false, [
            'yform_manager_table_view' => '|' . $this->tableA . '|',
            'yform_manager_table_edit' => '|' . $this->tableA . '|',
        ]);

        Authorization::$tableAuthorizations = null;

        $a = Table::require($this->tableA);

        $this->assertTrue(Authorization::onAttribute('VIEW', $a, $user), 'the listed table must be viewable');
        $this->assertTrue(Authorization::onAttribute('EDIT', $a, $user), 'the listed table must be editable');
    }

    /**
     * A table reachable through a relation of a viewable table inherits VIEW — without it
     * the relation field could not resolve its labels. EDIT is not inherited, so a user
     * granted only table A cannot change the rows of the table behind the relation.
     */
    public function testNonAdminInheritsViewButNotEditThroughARelation(): void
    {
        $user = $this->makeUser(false, [
            'yform_manager_table_view' => '|' . $this->tableA . '|',
            'yform_manager_table_edit' => '|' . $this->tableA . '|',
        ]);

        Authorization::$tableAuthorizations = null;

        // tableA has a be_manager_relation pointing at tableB (see setUpBeforeClass).
        $b = Table::require($this->tableB);

        $this->assertTrue(Authorization::onAttribute('VIEW', $b, $user), 'the related table inherits VIEW');
        $this->assertFalse(Authorization::onAttribute('EDIT', $b, $user), 'the related table must not inherit EDIT');
    }

    public function testNonAdminWithViewOnlyRoleCannotEdit(): void
    {
        $user = $this->makeUser(false, [
            'yform_manager_table_view' => '|' . $this->tableA . '|',
        ]);

        Authorization::$tableAuthorizations = null;

        $a = Table::require($this->tableA);
        $this->assertTrue(Authorization::onAttribute('VIEW', $a, $user));
        $this->assertFalse(Authorization::onAttribute('EDIT', $a, $user), 'view permission alone must not grant edit');
    }
}
