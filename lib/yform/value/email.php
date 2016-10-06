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

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(4) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

    }

    function getDescription()
    {
        return 'email|name|label|defaultwert|[no_db]|cssclassname';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'email',
            'values' => array(
                'name'      => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'     => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'default'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_email_default")),
                'no_db'     => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'attributes'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_attributes"), 'notice' => rex_i18n::msg("yform_values_defaults_attributes_notice")),
                'notice'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_notice")),
            ),
            'description' => rex_i18n::msg("yform_values_email_description"),
            'dbtype' => 'text',
            'famous' => false
        );

    }
}
