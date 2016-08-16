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
        if ($this->getElement('format') != '') {
            $format = $this->getElement('format');
            if ($format == 'mysql') {
                $format = 'Y-m-d H:i:s';
            }
        }

        // 0 = immer setzen, 1 = nur wenn leer / create
        if ($this->getElement('only_empty') != 1 || !isset($this->params['sql_object']) || !$this->params['sql_object']->getValue($this->getName())) {
            $this->setValue(date($format));
        }

    }

    function enterObject()
    {
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getValue() && $this->getElement('no_db') != 'no_db') {
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
                'name'  =>  array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'format' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_datestamp_format")),
                'no_db' => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'only_empty' => array( 'type' => 'select',  'label' => rex_i18n::msg("yform_values_datestamp_only_empty"), 'default' => '0', 'options' => 'immer=0,nur wenn leer=1' ),
            ),
            'description' => rex_i18n::msg("yform_values_datestamp_description"),
            'dbtype' => 'varchar(255)'
        );


    }


}
