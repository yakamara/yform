<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_uniqueform extends rex_yform_value_abstract
{

    function enterObject()
    {

        $table = $this->getElement(2);

        if (!$this->params['send']) {
            $this->setValue(md5($_SERVER['REMOTE_ADDR'] . time()));

        } else {
            $sql = 'select ' . $this->getName() . ' from ' . $table . ' WHERE ' . $this->getName() . '="' . $this->getValue() . '" LIMIT 1';
            $cd = rex_sql::factory();
            if ($this->params['debug']) {
                $cd->debugsql = true;
            }

            $cd->setQuery($sql);
            if ($cd->getRows() == 1) {
                $this->params['warning'][$this->getId()] = $this->getElement(3);
                $this->params['warning_messages'][$this->getId()] = $this->getElement(3);
            }

        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
        $this->params['value_pool']['email'][$this->getName()] = stripslashes($this->getValue());
        $this->params['value_pool']['sql'][$this->getName()] = stripslashes($this->getValue());

    }

    function getDescription()
    {
        return 'uniqueform -> Beispiel: uniqueform|name|table|Fehlermeldung';
    }
}
