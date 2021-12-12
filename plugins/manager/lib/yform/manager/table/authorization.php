<?php

class rex_yform_manager_table_authorization
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    public static $tableAuthorizations = null;

    /**
     * @param string                  $attribute
     * @param rex_yform_manager_table $userTable
     * @param null|rex_user           $user
     * @return bool
     */
    public static function onAttribute(string $attribute, rex_yform_manager_table $userTable, rex_user $user = null): bool
    {
        if (null !== self::$tableAuthorizations) {
            if (array_key_exists($attribute, self::$tableAuthorizations[$userTable->getTableName()] ?? [])) {
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

        return self::onAttribute($attribute, $userTable, $user);
    }

    /**
     * @param rex_yform_manager_table $table
     * @param null|rex_user           $user
     * @return bool
     */
    private static function canView(rex_yform_manager_table $table, rex_user $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        /** @var rex_yform_manager_table_perm_view $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_view');

        return $complexPerm->hasPerm($table->getTableName());
    }

    /**
     * @param rex_yform_manager_table $table
     * @param null|rex_user           $user
     * @return bool
     */
    private static function canEdit(rex_yform_manager_table $table, rex_user $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        /** @var rex_yform_manager_table_perm_edit $complexPerm */
        $complexPerm = $user->getComplexPerm('yform_manager_table_edit');

        return $complexPerm->hasPerm($table->getTableName());
    }
}
