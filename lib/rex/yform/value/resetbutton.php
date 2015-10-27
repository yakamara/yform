<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_resetbutton extends rex_yform_value_abstract
{

    function enterObject()
    {
        $this->setValue($this->getElement(3));

        $this->params['form_output'][$this->getId()] = $this->parse('value.resetbutton.tpl.php');

    }

    function getDescription()
    {
        return 'resetbutton -> Beispiel: resetbutton|name|label|value|cssclassname';
    }
}
