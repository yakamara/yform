<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_readtable extends rex_yform_action_abstract
{
    public function executeAction()
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

        if (count($data) == 1) {
            $data = current($data);
            foreach ($data as $k => $v) {
                $this->params['value_pool']['email'][$k] = $v;
            }
        }
    }

    public function getDescription()
    {
        return 'action|readtable|tablename|feldname|label';
    }
}
