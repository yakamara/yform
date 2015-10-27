<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_email extends rex_yform_value_abstract
{

    function enterObject()
    {

        $this->setValue((string) $this->getValue());

        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement(3));
        }

        $this->params['form_output'][$this->getId()] = $this->parse(array('value.email.tpl.php', 'value.text.tpl.php'), array('type' => 'email'));

        $this->params['value_pool']['email'][$this->getName()] = stripslashes($this->getValue());
        if ($this->getElement(4) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

    }

    function getDescription()
    {
        return 'email -> Beispiel: email|name|label|defaultwert|[no_db]|cssclassname';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'email',
            'values' => array(
                'name'      => array( 'type' => 'name',   'label' => 'Feld' ),
                'label'     => array( 'type' => 'text',    'label' => 'Bezeichnung'),
                'default'   => array( 'type' => 'text',    'label' => 'Defaultwert'),
                'no_db'     => array( 'type' => 'no_db',   'label' => 'Datenbank',  'default' => 0),
                'css_class' => array( 'type' => 'text',    'label' => 'cssclassname'),
            ),
            'description' => 'Ein einfaches Textfeld als Eingabe',
            'dbtype' => 'text',
            'famous' => false
        );

    }
}
