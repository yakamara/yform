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
        $labels = explode(",",$this->getElement(3));

        if ($query == '') {
            if ($this->params['debug']) {
                echo 'ActionQuery Error: no query';
            }
            return;
        }

        $sql = rex_sql::factory();
        if ($this->params['debug']) {
            $sql->setDebug();
        }

        $params = [];
        foreach($labels as $label) {
            $label = trim($label);
            $params[] = $this->params['value_pool']['sql'][$label];
        }

        $sql->setQuery($query, $params);

    }

    function getDescription()
    {
        return 'action|db_query|query|labels[name,email,id]';
    }

}
