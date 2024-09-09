<?php

namespace Yakamara\YForm\Action;

use rex_sql;

use function count;

class ReadTable extends AbstractAction
{
    public function executeAction(): void
    {
        if (!isset($this->params['value_pool']['email'][$this->getElement(4)])) {
            return;
        }
        $value = $this->params['value_pool']['email'][$this->getElement(4)];

        $gd = rex_sql::factory();
        if ($this->params['debug']) {
            $gd->setDebug();
        }
        $data = $gd->getArray('select * from ' . $this->getElement(2) . ' where ' . $gd->escapeIdentifier($this->getElement(3)) . ' = ' . $gd->escape($value) . ' ');

        if (1 == count($data)) {
            $data = current($data);
            foreach ($data as $k => $v) {
                $this->params['value_pool']['email'][$k] = $v;
            }
        }
    }

    public function getDescription(): string
    {
        return 'action|readtable|tablename|feldname|label';
    }
}
