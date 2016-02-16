<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_readtable extends rex_yform_action_abstract
{

    function executeAction()
    {

        $value = '';
        if (!isset($this->params['value_pool']['email'][$this->getElement(4)])) {
            return;
        }
        $value = $this->params['value_pool']['email'][$this->getElement(4)];

        $gd = rex_sql::factory();
        if ($this->params['debug']) {
            $gd->debugsql = 1;
        }
        $gd->setQuery('select * from ' . $this->getElement(2) . ' where ' . $gd->escapeIdentifier($this->getElement(3)) . ' = ' . $gd->escape($value) . ' ');
        $data = $gd->getArray();

        if (count($data) == 1) {
            $data = current($data);
            foreach ($data as $k => $v) {
                $this->params['value_pool']['email'][$k] = $v;
            }
        }

        return;
    }

    function getDescription()
    {
        return 'action|readtable|tablename|feldname|label';
    }

}
