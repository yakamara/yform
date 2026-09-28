<?php

namespace Yakamara\YForm\Permission;

use Override;
use Redaxo\Core\Security\ComplexPermission;
use Redaxo\Core\Translation\I18n;
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
    public static function getFieldParams(): ?array
    {
        $options = [];
        foreach (Tables::all() as $key => $table) {
            $options[$key] = $table->label() . ' [' . $table->name . ']';
        }

        return [
            'label' => I18n::msg(static::labelKey()),
            'all_label' => I18n::msg(static::allLabelKey()),
            'options' => $options,
        ];
    }

    abstract protected static function labelKey(): string;

    abstract protected static function allLabelKey(): string;
}
