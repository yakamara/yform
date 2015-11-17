<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datestamp extends rex_yform_value_abstract
{

    function preValidateAction()
    {
        $format = 'Y-m-d';
        if ($this->getElement(2) != '') {
            $format = $this->getElement(2);
            if ($format == 'mysql') {
                $format = 'Y-m-d H:i:s';
            }
        }

        // 0 = immer setzen, 1 = nur wenn leer / create
        if ($this->getElement(4) != 1 || !isset($this->params['sql_object']) || !$this->params['sql_object']->getValue($this->getName())) {
            $this->setValue(date($format));
        }

    }

    function enterObject()
    {
        //$this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getValue() && $this->getElement(3) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'datestamp -> Beispiel: datestamp|name| [YmdHis/U/dmy/mysql] | [no_db] | [0-wird immer neu gesetzt,1-nur wenn leer]';
    }

    function getDefinitions()
    {

        return array(
            'type' => 'value',
            'name' => 'datestamp',
            'values' => array(
                'name'  =>  array( 'type' => 'name',   'label' => 'Name' ),
                'label' => array( 'type' => 'text',    'label' => 'Format [YmdHis/U/dmy/mysql]'),
                'no_db' => array( 'type' => 'no_db',   'label' => 'Datenbank',  'default' => 0),
                'only_empty' => array( 'type' => 'select',  'label' => 'Wann soll Wert gesetzt werden', 'default' => '0', 'options' => 'immer=0,nur wenn leer=1' ),
            ),
            'description' => 'Zeitstempel.',
            'dbtype' => 'varchar(255)'
        );


    }


}
