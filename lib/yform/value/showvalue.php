<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_showvalue extends rex_yform_value_abstract
{

    function enterObject()
    {

        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement(3));
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php');

        $this->params['value_pool']['email'][$this->getName()] = stripslashes($this->getValue());

    }

    function getDescription()
    {
        return 'showvalue -> Beispiel: showvalue|name|label|defaultwert';
    }
}
