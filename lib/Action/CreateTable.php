<?php

namespace Yakamara\YForm\Action;

use rex;
use rex_sql;
use function in_array;

class CreateTable extends AbstractAction
{
    public function executeAction(): void
    {
        $table_name = $this->getElement(2);
        $table_name = str_replace('%TABLE_PREFIX%', rex::getTablePrefix(), $table_name);
        $table_exists = false;
        $cols = [];

        $tables = rex_sql::factory()->getArray('show tables');
        foreach ($tables as $table) {
            if (current($table) == $table_name) {
                $table_exists = true;
                break;
            }
        }

        if (!$table_exists) {
            rex_sql::factory()->setQuery('CREATE TABLE `' . $table_name . '` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        foreach (rex_sql::factory()->getArray('show columns from ' . $table_name) as $k => $v) {
            $cols[] = $v['Field'];
        }

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if (!in_array($key, $cols)) {
                rex_sql::factory()->setQuery('ALTER TABLE `' . $table_name . '` ADD `' . $key . '` TEXT NOT NULL;');
            }
        }
    }

    public function getDescription(): string
    {
        return 'action|create_table|tablename';
    }
}
