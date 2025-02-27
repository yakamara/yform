<?php

namespace Yakamara\YForm\Action;

use Exception;
use rex_sql;

class DbQuery extends AbstractAction
{
    public function executeAction(): void
    {
        $query = trim($this->getElement(2));
        $labels = explode(',', $this->getElement(3));

        if ('' == $query) {
            if ($this->params['debug']) {
                echo 'ActionQuery Error: no query';
            }
            return;
        }

        try {
            $sql = rex_sql::factory();
            $sql->setDebug($this->params['debug']);

            $params = [];
            foreach ($labels as $label) {
                $label = trim($label);
                $params[] = $this->params['value_pool']['sql'][$label] ?? '';
            }

            $sql->setQuery($query, $params);
        } catch (Exception $e) {
            $this->params['form_show'] = true;
            $this->params['hasWarnings'] = true;
            if ($this->params['debug']) {
                $this->params['warning_messages'][] = $e->getMessage();
            } else {
                $this->params['warning_messages'][] = $this->params['Error-Code-QueryError'];
            }
        }
    }

    public function getDescription(): string
    {
        return 'action|db_query|query|labels[name,email,id]';
    }
}
