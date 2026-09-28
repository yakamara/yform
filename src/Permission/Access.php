<?php

namespace Yakamara\YForm\Permission;

use Redaxo\Core\Core;
use Redaxo\Core\Security\User;
use Yakamara\YForm\Table\Table;

/** Whether a backend user may view or edit the records of a table. Admins may do everything. */
final class Access
{
    public static function canEdit(Table $table, ?User $user = null): bool
    {
        $user ??= Core::getUser();
        if (null === $user) {
            return false;
        }
        if ($user->admin) {
            return true;
        }
        $perm = $user->getComplexPerm(TableEditPermission::KEY);
        return $perm instanceof TablePermission && $perm->hasTable($table->name);
    }

    public static function canView(Table $table, ?User $user = null): bool
    {
        $user ??= Core::getUser();
        if (null === $user) {
            return false;
        }
        if (self::canEdit($table, $user)) {
            return true;
        }
        $perm = $user->getComplexPerm(TableViewPermission::KEY);
        return $perm instanceof TablePermission && $perm->hasTable($table->name);
    }
}
