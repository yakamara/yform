<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_submit extends rex_yform_value_abstract
{

    function init()
    {
        $this->params['submit_btn_show'] = false;
    }

    function enterObject()
    {

        $real_value = $this->getElement(5);
        $value_on_button = $this->getElement(2);
        if ($real_value == "") {
            $real_value = $value_on_button;
        }

        if ($this->getValue() != $value_on_button) {
            $real_value = "";
        }

        $this->setValue($value_on_button);

        $this->params['form_output'][$this->getId()] = $this->parse('value.submit.tpl.php');

        if (!isset($this->params['value_pool']['email'][$this->getName()]) || $this->params['value_pool']['email'][$this->getName()] == "") {
            $this->params['value_pool']['email'][$this->getName()] = $real_value;
        }

        if ($this->getElement(3) != 'no_db') {
            if (!isset($this->params['value_pool']['sql'][$this->getName()]) || $this->params['value_pool']['sql'][$this->getName()] == "") {
                $this->params['value_pool']['sql'][$this->getName()] = $real_value;
            }
        }

    }

    function getDescription()
    {
        return 'submit -> Beispiel: submit|label|value_on_button|[no_db]|cssclassname|[value_to_save_if_clicked]';
    }
}
