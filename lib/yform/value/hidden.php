<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_hidden extends rex_yform_value_abstract
{

    public function setValue($value)
    {
        if ($this->getElement(3) == 'REQUEST' && isset($_REQUEST[$this->getElement(2)])) {
            $this->value = rex_request($this->getElement(2));

        } else {
            $this->value = $this->getElement(2);

        }

    }

    public function enterObject()
    {
        if ($this->getElement(3) == 'REQUEST') {
            $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(4) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'hidden|name|(default)value||[no_db]'."\n".'hidden|job_id|my_id|REQUEST|[no_db]';
    }

}
