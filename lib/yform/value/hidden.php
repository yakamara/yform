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
        if ('REQUEST' == $this->getElement(3) && isset($_REQUEST[$this->getElement(2)])) {
            $this->value = rex_request($this->getElement(2));
        } elseif ('GET' == $this->getElement(3) && isset($_GET[$this->getElement(2)])) {
            $this->value = rex_get($this->getElement(2));
        } elseif ('POST' == $this->getElement(3) && isset($_POST[$this->getElement(2)])) {
            $this->value = rex_post($this->getElement(2));
        } elseif ('SESSION' == $this->getElement(3) && null !== rex_session($this->getElement(2), 'string', null)) {
            $this->value = rex_session($this->getElement(2));
        } else {
            $this->value = $this->getElement(2);
        }
    }

    public function enterObject()
    {
        if ($this->needsOutput() && (in_array($this->getElement(3), ['GET', 'POST', 'SESSION', 'REQUEST']))) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php', ['fieldName' => $this->getName()]);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb('4')) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'hidden|fieldname|value||[no_db]'."\n".'hidden|fieldname|key|REQUEST/GET/POST/SESSION|[no_db]';
    }
}
