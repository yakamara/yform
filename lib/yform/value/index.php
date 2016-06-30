<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_index extends rex_yform_value_abstract
{

    function postFormAction()
    {
    }

    function getDescription()
    {
        return 'index -> Beispiel: index|name|label1,label2,label3|[no_db]|[func/md5/sha]';
    }

    function enterObject()
    {

        if ($this->params['send'] == 1) {

            $index_labels = explode(',', $this->getElement(2));

            $value = '';
            foreach ($this->params['value_pool']['sql'] as $name => $v) {
                if (in_array($name, $index_labels)) {
                        $value .= $v;
                }
            }

            $fnc = trim($this->getElement(4));
            if (function_exists($fnc)) {
                $value = call_user_func($fnc, $value);
            }

            $this->setValue($value);

            $this->params['value_pool']['email'][$this->getName()] = $value;
            if ($this->getElement(3) != 'no_db') {
                $this->params['value_pool']['sql'][$this->getName()] = $value;
            }

        }
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'index',
            'values' => array(
                'name'     => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'names'    => array( 'type' => 'select_names',  'label' => rex_i18n::msg("yform_values_index_names")),
                'no_db'    => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'function' => array( 'type' => 'select',  'label' => rex_i18n::msg("yform_values_index_function"), 'default' => '0', 'options' => 'Keine Funktion=,md5,sha1' ),
            ),
            'description' => rex_i18n::msg("yform_values_index_description"),
            'dbtype' => 'text'
        );

    }
}
