<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_generate_key extends rex_yform_value_abstract
{

    function enterObject()
    {
        $this->setValue(md5($this->params['form_name'] . substr(md5(microtime()), 0, 6)));
        $this->params['form_output'][$this->getId()] = '';
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(2) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'generate_key -> Beispiel: generate_key|name|[no_db]';
    }
}
