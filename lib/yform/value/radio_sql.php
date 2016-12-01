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
        $sql = $this->getElement('query');

        $teams = rex_sql::factory();
        $teams->setDebug($this->params['debug']);
        $teams->setQuery($sql);

        $options = array();
        foreach ($teams->getArray() as $t) {
            $v = $t['name'];
            $k = $t['id'];
            $options[$k] = $v;
        }

        $default = $this->getElement('default');
        if (!array_key_exists($default, $options)) {
            $default = key($options);
        }

        if (!array_key_exists($this->getValue(), $options)) {
            $this->setValue($default);
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.radio.tpl.php', compact('options'));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'radio_sql|name|label|select id,name from table order by name|[defaultvalue]|';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'radio_sql',
            'values' => array(
                'name'      => array( 'type' => 'name', 'label' => rex_i18n::msg("yform_values_defaults_name") ),
                'label'     => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_label")),
                'query'     => array( 'type' => 'text', 'label' => 'Query mit "select id, name from .."'),
                'default'   => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_radio_default")),
                'attributes'=> array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_attributes"), 'notice' => rex_i18n::msg("yform_values_defaults_attributes_notice")),
                'notice'    => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_notice")),
                'no_db'     => array( 'type' => 'no_db', 'label' => rex_i18n::msg("yform_values_defaults_table"), 'default' => 0),
            ),
            'description' => 'Hiermit kann man SQL Abfragen als Radioliste nutzen',
            'dbtype' => 'text'
        );
    }

}
