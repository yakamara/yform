<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_radio extends rex_yform_value_abstract
{

    function enterObject()
    {

        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        $options = $this->getArrayFromString($this->getElement(3));

        $default = $this->getElement('default');
        if ($default === false) {
          $default = key($options);
        }

        if (!array_key_exists($this->getValue(), $options)) {
            $this->setValue($default);
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.radio.tpl.php', compact('options'));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(4) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

    }

    function getDescription()
    {
        return 'radio -> Beispiel: radio|name|label|Frau=w,Herr=m|[no_db]|[defaultwert]';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'radio',
            'values' => array(
                'name'     => array( 'type' => 'name',   'label' => 'Feld' ),
                'label'    => array( 'type' => 'text',    'label' => 'Bezeichnung'),
                'options'  => array( 'type' => 'text',    'label' => 'Selectdefinition, kommasepariert'),
                'no_db'    => array( 'type' => 'no_db',   'label' => 'Datenbank',          'default' => 0),
                'default'  => array( 'type' => 'text',    'label' => 'Defaultwert'),
            ),
            'description' => rex_i18n::msg("yform_values_radio_description"),
            'dbtype' => 'text'
        );

    }

}
