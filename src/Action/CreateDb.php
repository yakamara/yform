<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\Attribute\AsAction;
use Redaxo\Core\Database\Sql;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsAction('createdb')]
class CreateDb extends AbstractAction
{
    public function executeAction(): void
    {
        $table_name = $this->getElement(2);
        $table_exists = false;

        $tables = Sql::factory()->getArray('show tables');
        foreach ($tables as $table) {
            if (current($table) == $table_name) {
                $table_exists = true;
                break;
            }
        }

        if (!$table_exists) {
            Sql::factory()->setQuery('CREATE TABLE `' . $table_name . '` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        $cols = [];
        foreach (Sql::factory()->getArray('show columns from ' . $table_name) as $k => $v) {
            $cols[] = $v['Field'];
        }

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if (!in_array($key, $cols)) {
                Sql::factory()->setQuery('ALTER TABLE `' . $table_name . '` ADD `' . $key . '` TEXT NOT NULL;');
            }
        }
    }

    public function getDescription(): string
    {
        return 'action|createdb|tablename';
    }

    public function isDeprecated(): bool
    {
        return true;
    }
}
