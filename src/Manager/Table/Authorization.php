<?php

namespace Yakamara\YForm\Manager\Table;

use Redaxo\Core\Security\User;
use Yakamara\YForm\Manager\Table\Perm\Edit;
use Yakamara\YForm\Manager\Table\Perm\View;

class Authorization
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    /** @var null|array<int|string, array<string, array<string, int>>> */
    public static ?array $tableAuthorizations = null;

    public static function onAttribute(string $attribute, Table $userTable, ?User $user = null): bool
    {
        $userKey = $user ? (int) $user->id : 'guest';

        if (null !== self::$tableAuthorizations && array_key_exists($userKey, self::$tableAuthorizations)) {
            $perms = self::$tableAuthorizations[$userKey][$userTable->getTableName()] ?? [];
            return array_key_exists($attribute, $perms);
        }

        if (null === self::$tableAuthorizations) {
            self::$tableAuthorizations = [];
        }
        self::$tableAuthorizations[$userKey] = [];

        foreach (Table::getAll() as $table) {
            if (self::canEdit($table, $user)) {
                self::$tableAuthorizations[$userKey][$table->getTableName()][self::VIEW] = 1;
                self::$tableAuthorizations[$userKey][$table->getTableName()][self::EDIT] = 1;
            } elseif (self::canView($table, $user)) {
                self::$tableAuthorizations[$userKey][$table->getTableName()][self::VIEW] = 1;
            }

            foreach ($table->getRelationTableNames() as $relationTableName) {
                if (isset(self::$tableAuthorizations[$userKey][$table->getTableName()])) {
                    self::$tableAuthorizations[$userKey][$relationTableName][self::VIEW] = 1;
                }
            }
        }

        return self::onAttribute($attribute, $userTable, $user);
    }

    private static function canView(Table $table, ?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->admin) {
            return true;
        }

        /** @var View $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_view');

        return null !== $complexPerm && $complexPerm->hasPerm($table->getTableName());
    }

    private static function canEdit(Table $table, ?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->admin) {
            return true;
        }

        /** @var Edit $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_edit');

        return null !== $complexPerm && $complexPerm->hasPerm($table->getTableName());
    }
}
