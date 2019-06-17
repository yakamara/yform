<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_hidden extends rex_yform_value_abstract
{
    public function setValue($value)
    {
        if ($this->getElement(3) == 'REQUEST' && isset($_REQUEST[$this->getElement(2)])) {
            $this->value = rex_request($this->getElement(2));
        } else if ($this->getElement(3) == 'GET' && isset($_REQUEST[$this->getElement(2)])) {
            $this->value = rex_get($this->getElement(2));
        } else if ($this->getElement(3) == 'POST' && isset($_REQUEST[$this->getElement(2)])) {
            $this->value = rex_post($this->getElement(2));
        } else if ($this->getElement(3) == 'SESSION' && isset($_REQUEST[$this->getElement(2)])) {
            $this->value = rex_session($this->getElement(2));
        } else {
            $this->value = $this->getElement(2);
        }
    }

    public function enterObject()
    {
        if ($this->needsOutput() && ($this->getElement(3) == 'REQUEST' || $this->getElement(3) == 'GET' || $this->getElement(3) == 'POST' || $this->getElement(3) == 'SESSION')) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb('4')) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'hidden|name|(default)value||[no_db]'."\n".'hidden|job_id|my_id|REQUEST/GET/POST/SESSION|[no_db]';
    }
}
