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
        return [
            'label' => rex_i18n::msg('yform_manager_table'),
            'all_label' => rex_i18n::msg('yform_manager_tables_view'),
            'sql_options' => 'select concat(`name`," [", `table_name`,"]") as name, table_name as id from ' . rex::getTablePrefix() . 'yform_table where status = 1 order by prio',
        ];
    }
}
