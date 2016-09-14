<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_fieldset extends rex_yform_value_abstract
{

    function enterObject()
    {
        $output = '';

        $option = $this->getElement(4);
        $options = array('onlyclose', 'onlycloseall', 'onlyopen', 'closeandopen');
        if (!in_array($option, $options)) {
            $option = 'closeandopen';
        }

        switch ($option) {
            case 'closeandopen':
            case 'onlyclose':
                if ($this->params['fieldsets_opened'] > 0) {
                    $output .= $this->parse('value.fieldset.tpl.php', array('option' => 'close'));
                    $this->params['fieldsets_opened']--;
                }
                break;
            case 'onlycloseall':
                for ($i = 0; $i < $this->params['fieldsets_opened']; $i++) {
                    $output .= $this->parse('value.fieldset.tpl.php', array('option' => 'close'));
                }
                $this->params['fieldsets_opened'] = 0;
                break;
            case 'onlyopen':
                break;
        }

        switch ($option) {
            case 'closeandopen':
            case 'onlyopen':
                $this->params['fieldsets_opened']++;
                $output .= $this->parse('value.fieldset.tpl.php', array('option' => 'open'));
                break;
        }

        $this->params['form_output'][$this->getId()] = $output;

    }

    function getDescription()
    {
        return 'fieldset -> Beispiel: fieldset|name|label|[class]|[onlyclose/onlycloseall/onlyopen/closeandopen]';

    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'fieldset',
            'values' => array(
                'name'  => array( 'type' => 'name',  'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label' => array( 'type' => 'text',  'label' => rex_i18n::msg("yform_values_defaults_label")),
            ),
            'description' => rex_i18n::msg("yform_values_fieldset_description"),
            'dbtype' => 'none',
            'is_searchable' => false,
            'is_hiddeninlist' => true,
            'multi_edit' => 'always',
        );
    }

}
