<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_password extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement(3));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.password.tpl.php', 'value.text.tpl.php'], ['type' => 'password', 'value' => '']);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement('no_db') != 1) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'password|name|label|default_value|[no_db]';
    }
}
