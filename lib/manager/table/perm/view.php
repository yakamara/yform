<?php

/**
 * @package redaxo\core\login
 */
class rex_yform_manager_table_perm_view extends rex_complex_perm
{
    public function hasPerm($table_name)
    {
        return $this->hasAll() || in_array($table_name, $this->perms);
    }

    public static function getFieldParams()
    {
        $arrayOptions = [];
        foreach (rex_yform_manager_table::getAll() as $table) {
            $arrayOptions[$table->getTableName()] = $table->getNameLocalized() . ' [' . $table->getTableName() . ']';
        }

        return [
            'label' => rex_i18n::msg('yform_manager_table'),
            'all_label' => rex_i18n::msg('yform_manager_tables_view'),
            'options' => $arrayOptions,
        ];
    }
}
