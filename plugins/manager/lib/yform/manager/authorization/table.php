<?php

class rex_yform_manager_table_authorization
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    public static $tableAuthorizations = null;

    public static function onAttribute(string $attribute, rex_yform_manager_table $table, rex_user $user = null): bool
    {
        if (null !== self::$tableAuthorizations) {
            if (array_key_exists($attribute, self::$tableAuthorizations[$table->getTableName()] ?? [])) {
                return true;
            }
            return false;
        }

        self::$tableAuthorizations = [];

        foreach (rex_yform_manager_table::getAll() as $table) {

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

        return self::onAttribute($attribute, $table, $user);
    }

    private static function canView(rex_yform_manager_table $table, rex_user $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $userRoles = [];
        if ('' != $user->getValue('role')) {
            $userRoles = explode(',', $user->getValue('role'));
        }

        if ('' == $table['exclusive_view_roles']) {
            return false;
        }

        foreach (explode(',', $table['exclusive_view_roles']) as $roleId) {
            if (in_array($roleId, $userRoles, true)) {
                return true;
            }
        }
        return false;
    }

    private static function canEdit(rex_yform_manager_table $table, rex_user $user = null): bool
    {

        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $userRoles = [];
        if ('' != $user->getValue('role')) {
            $userRoles = explode(',', $user->getValue('role'));
        }

        if ('' == $table['exclusive_edit_roles']) {
            return false;
        }

        foreach (explode(',', $table['exclusive_edit_roles']) as $roleId) {
            if (in_array($roleId, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
