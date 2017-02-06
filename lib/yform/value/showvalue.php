<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_showvalue extends rex_yform_value_abstract
{

    function enterObject()
    {

        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

    }

    function getDescription()
    {
        return 'showvalue|name|label|defaultwert|notice';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'showvalue',
            'values' => array(
                'name'      => array( 'type' => 'name',    'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'     => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'default'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_text_default")),
                'notice'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_notice")),
            ),
            'description' => rex_i18n::msg("yform_values_showvalue_description"),
            'dbtype' => 'text'
        );

    }

}
