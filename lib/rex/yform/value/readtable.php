<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_readtable extends rex_yform_value_abstract
{

    function enterObject()
    {
        foreach ($this->params['value_pool']['email'] as $k => $v) {
            if ($this->getElement(3) == $k) {
                $value = $v;
            }
        }
        $gd = rex_sql::factory();
        $gd->setQuery('select * from ' . $this->getElement(1) . ' where ' . $this->getElement(2) . '="' . addslashes($v) . '"');

        if ($gd->getRows() == 1) {
            $ar = $gd->get_array();
            foreach ($ar[0] as $k => $v) {
                $this->params['value_pool']['email'][$k] = $v;
            }
        }
        return;
    }

    function getDescription()
    {
        return 'readtable|tablename|feldname|label';
    }

}
