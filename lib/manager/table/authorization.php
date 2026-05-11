<?php

class rex_yform_manager_table_authorization
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    /** @var null|array<int|string, array<string, array<string, int>>> */
    public static ?array $tableAuthorizations = null;

    public static function onAttribute(string $attribute, rex_yform_manager_table $userTable, ?rex_user $user = null): bool
    {
        $userKey = $user ? (int) $user->getId() : 'guest';

        if (null !== self::$tableAuthorizations && array_key_exists($userKey, self::$tableAuthorizations)) {
            $perms = self::$tableAuthorizations[$userKey][$userTable->getTableName()] ?? [];
            return array_key_exists($attribute, $perms);
        }

        if (null === self::$tableAuthorizations) {
            self::$tableAuthorizations = [];
        }
        self::$tableAuthorizations[$userKey] = [];

        foreach (rex_yform_manager_table::getAll() as $table) {
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

    private static function canView(rex_yform_manager_table $table, ?rex_user $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        /** @var rex_yform_manager_table_perm_view $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_view');

        return null !== $complexPerm && $complexPerm->hasPerm($table->getTableName());
    }

    private static function canEdit(rex_yform_manager_table $table, ?rex_user $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        /** @var rex_yform_manager_table_perm_edit $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_edit');

        return null !== $complexPerm && $complexPerm->hasPerm($table->getTableName());
    }
}
