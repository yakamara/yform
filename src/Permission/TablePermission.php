<?php

namespace Yakamara\YForm\Permission;

use Override;
use Redaxo\Core\Security\ComplexPermission;
use Redaxo\Core\Security\PermissionChoices;
use Redaxo\Core\Translation\Translatable;
use Yakamara\YForm\Table\Tables;

use function in_array;
use function is_array;

/**
 * Role permission listing the tables a user may work with. Two instances are registered: one for viewing, one
 * for editing (see {@see Access}).
 */
abstract class TablePermission extends ComplexPermission
{
    public function hasTable(string $key): bool
    {
        return $this->hasAll() || (is_array($this->perms) && in_array($key, $this->perms, true));
    }

    #[Override]
    public static function getChoices(): PermissionChoices
    {
        $choices = [];
        foreach (Tables::all() as $key => $table) {
            $choices[$key] = $table->label() . ' [' . $table->name . ']';
        }

        return new PermissionChoices(new Translatable(static::labelKey()), new Translatable(static::allLabelKey()), $choices);
    }

    abstract protected static function labelKey(): string;

    abstract protected static function allLabelKey(): string;
}
