<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_radio_sql extends rex_yform_value_abstract
{

    function enterObject()
    {
        $sql = $this->getElement(3);

        $teams = rex_sql::factory();
        $teams->debugsql = $this->params['debug'];
        $teams->setQuery($sql);

        $options = array();
        foreach ($teams->getArray() as $t) {
            $v = $t['name'];
            $k = $t['id'];
            $options[$k] = $v;
        }

        if ($this->getElement(4) != '') {
            $this->setValue($this->getElement(4));
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.radio.tpl.php', compact('options'));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(5) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'radio_sql -> Beispiel: select_sql|name|label|select id,name from table order by name|[defaultvalue]|[no_db]|';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'radio_sql',
            'values' => array(
                'name'  => array( 'type' => 'name',    'label' => 'Name' ),
                'label' => array( 'type' => 'text',    'label' => 'Bezeichnung'),
                'query' => array( 'type' => 'text',    'label' => 'Query mit "select id, name from .."')
            ),
            'description' => 'Hiermit kann man SQL Abfragen als Radioliste nutzen',
            'dbtype' => 'text'
        );
    }

}
