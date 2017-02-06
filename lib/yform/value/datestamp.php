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

        $default_value = date($format);
        $value = $this->getValue();

        if ($this->getElement('only_empty') == 2) {
            // wird nicht gesetzt

        } elseif ($this->getElement('only_empty') != 1) { // -> == 0
            // wird immer neu gesetzt
            $value = $default_value;
        } elseif ($this->getValue() != "") {
            // wenn Wert vorhanden ist direkt zurück
        } elseif (isset($this->params['sql_object']) && $this->params['sql_object']->getValue($this->getName()) != "") {
            // sql object vorhanden und Wert gesetzt ?
        } else {
            $value = $default_value;
        }

        $this->setValue($value);

    }

    function enterObject()
    {

        if ($this->needsOutput() && $this->getElement('show_value') == 1 && $this->getValue() != "") {
            $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getValue() && $this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'datestamp|name| [YmdHis/U/dmy/mysql] | [no_db] | [0-wird immer neu gesetzt,1-nur wenn leer,2-nie],';
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
                'only_empty' => array( 'type' => 'select',  'label' => rex_i18n::msg("yform_values_datestamp_only_empty"), 'default' => '0', 'options' => 'immer=0,nur wenn leer=1, nie=2' ),
                'show_value' => array( 'type' => 'checkbox',  'label' => rex_i18n::msg("yform_values_defaults_showvalue"), 'default' => '0', 'options' => '0,1' ),

            ),
            'description' => rex_i18n::msg("yform_values_datestamp_description"),
            'dbtype' => 'varchar(255)',
            'multi_edit' => 'always',
        );


    }


}
