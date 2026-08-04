<?php

namespace Yakamara\YForm\Manager\Table\Perm;

use Override;
use function in_array;
use function is_array;
use Redaxo\Core\Security\ComplexPermission;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Manager\Table\Table;

/**
 * @package redaxo\core\login
 */
class View extends ComplexPermission
{
    public function hasPerm(string $tableName): bool
    {
        // hasAll() already covers the "all" case, so $this->perms is an array by the time it is read.
        return $this->hasAll() || (is_array($this->perms) && in_array($tableName, $this->perms, true));
    }

    #[Override]
    public static function getFieldParams(): ?array
    {
        $arrayOptions = [];
        foreach (Table::getAll() as $table) {
            $arrayOptions[$table->getTableName()] = $table->getNameLocalized() . ' [' . $table->getTableName() . ']';
        }

        return [
            'label' => I18n::msg('yform_manager_table'),
            'all_label' => I18n::msg('yform_manager_tables_view'),
            'options' => $arrayOptions,
        ];
    }
}
