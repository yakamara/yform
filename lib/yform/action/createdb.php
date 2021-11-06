<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_createdb extends rex_yform_action_abstract
{
    public function executeAction()
    {
        $table_name = $this->getElement(2);
        $table_exists = false;

        $tables = rex_sql::factory($this->params['db_connection_id'])->getArray('show tables');
        foreach ($tables as $table) {
            if (current($table) == $table_name) {
                $table_exists = true;
                break;
            }
        }

        if (!$table_exists) {
            rex_sql::factory($this->params['db_connection_id'])->setQuery('CREATE TABLE `' . $table_name . '` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        foreach (rex_sql::factory($this->params['db_connection_id'])->getArray('show columns from ' . $table_name) as $k => $v) {
            $cols[] = $v['Field'];
        }

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if (!in_array($key, $cols)) {
                rex_sql::factory($this->params['db_connection_id'])->setQuery('ALTER TABLE `' . $table_name . '` ADD `' . $key . '` TEXT NOT NULL;');
            }
        }
    }

    public function getDescription()
    {
        return 'action|createdb|tablename';
    }

    public function isDeprecated()
    {
        return true;
    }
}
