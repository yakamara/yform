<?php

namespace Yakamara\YForm\Manager\Table\Perm;

use rex_complex_perm;
use rex_i18n;
use Yakamara\YForm\Manager\Table\Table;

use function in_array;

class View extends rex_complex_perm
{
    public function hasPerm($table_name)
    {
        return $this->hasAll() || in_array($table_name, $this->perms);
    }

    public static function getFieldParams()
    {
        $arrayOptions = [];
        foreach (Table::getAll() as $table) {
            $arrayOptions[$table->getTableName()] = $table->getNameLocalized() . ' [' . $table->getTableName() . ']';
        }

        return [
            'label' => rex_i18n::msg('yform_manager_table'),
            'all_label' => rex_i18n::msg('yform_manager_tables_view'),
            'options' => $arrayOptions,
        ];
    }
}
