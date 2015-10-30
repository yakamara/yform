<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_db_query extends rex_yform_action_abstract
{

    function executeAction()
    {

        $query = trim($this->getElement(2));

        if ($query == '') {
            if ($this->params['debug']) {
                echo 'ActionQuery Error: no query';
            }
            return;
        }

        $sql = rex_sql::factory();
        if ($this->params['debug']) {
            $sql->debugsql = true;
        }

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            $query = str_replace('###' . $key . '###', addslashes($value), $query);
        }

        $sql->setQuery($query);

        if ( $sql->getError() != '') {
            $this->params['form_show'] = true;
            $this->params['hasWarnings'] = true;
            $this->params['warning_messages'][] = $this->getElement(3);
        }

    }

    function getDescription()
    {
        return 'action|db_query|query|Fehlermeldung';
    }

}
