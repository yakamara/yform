<?php

namespace Yakamara\YForm\Manager\Table;

use rex_user;
use Yakamara\YForm\Manager\Table\Perm\Edit;
use Yakamara\YForm\Manager\Table\Perm\View;

use function array_key_exists;

class Authorization
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    /** @var array<string, mixed>|null */
    public static ?array $tableAuthorizations = null;

    public static function onAttribute(string $attribute, Table $userTable, ?rex_user $user = null): bool
    {
        if (null !== self::$tableAuthorizations) {
            if (array_key_exists($attribute, self::$tableAuthorizations[$userTable->getTableName()] ?? [])) {
                return true;
            }
            return false;
        }

        self::$tableAuthorizations = [];

        foreach (Table::getAll() as $table) {
            if (self::canEdit($table, $user)) {
                self::$tableAuthorizations[$table->getTableName()][self::VIEW] = 1;
                self::$tableAuthorizations[$table->getTableName()][self::EDIT] = 1;
            } elseif (self::canView($table, $user)) {
                self::$tableAuthorizations[$table->getTableName()][self::VIEW] = 1;
            }

            foreach ($table->getRelationTableNames() as $relationTableName) {
                if (isset(self::$tableAuthorizations[$table->getTableName()])) {
                    self::$tableAuthorizations[$relationTableName][self::VIEW] = 1;
                }
            }
        }

        return self::onAttribute($attribute, $userTable, $user);
    }

    private static function canView(Table $table, ?rex_user $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        /** @var View $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_view');

        return null !== $complexPerm && $complexPerm->hasPerm($table->getTableName());
    }

    private static function canEdit(Table $table, ?rex_user $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        /** @var Edit $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_edit');

        return null !== $complexPerm && $complexPerm->hasPerm($table->getTableName());
    }
}
