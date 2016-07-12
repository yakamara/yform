<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_hashvalue extends rex_yform_value_abstract
{
    function postFormAction()
    {
        if ($this->params['value_pool']['email'][$this->getElement('field')] != '') {

            $salt = $this->getElement('salt');
            $origin = $this->params['value_pool']['email'][$this->getElement(3)];
            $func = $this->getElement('function');

            if ($func == "" || !function_exists($func)) {
                $func = "md5";
            }

            $hash = hash($func, $origin . $salt);

            $this->params['value_pool']['email'][$this->getName()] = $hash;

            if ($this->getElement('no_db') != 'no_db') {
                $this->params['value_pool']['sql'][$this->getName()] = $hash;
            }
        } else {
            $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'hashvalue -> Beispiel: hashvalue|name|[title]|field|(md5/sha1/sha512/...)|[salt]|[no_db]';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'hashvalue',
            'values' => array(
                'name'     => array( 'type' => 'name',    'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'field'    => array( 'type' => 'select_name',    'label' => rex_i18n::msg("yform_values_hashvalue_field")),
                'function' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_hashvalue_function")),
                'salt'     => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_hashvalue_salt")),
                'no_db'     => array( 'type' => 'no_db',  'label' => rex_i18n::msg("yform_values_defaults_table")),
            ),
            'description' => rex_i18n::msg("yform_values_hashvalue_description"),
            'dbtype' => 'text'
        );
    }
}
