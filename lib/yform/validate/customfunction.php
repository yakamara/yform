<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_customfunction extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $label = $this->getElement('name');
            $func = $this->getElement('function');
            $parameter = $this->getElement('params');

            $comparator = true;
            if (is_string($func) && substr($func, 0, 1) == '!') {
                $comparator = false;
                $func = substr($func, 1);
            }

            $Object = $this->getValueObject($label);

            if (!is_callable($func)) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = 'ERROR: customfunction "' . $func . '" not found';

            } else if (call_user_func($func, $label, $Object->getValue(), $parameter, $this) === $comparator) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');

            }

        }
    }

    function getDescription()
    {
        return 'customfunction -> prüft über customfunc, beispiel: validate|customfunction|label|[!]function/class::method|weitere_parameter|warning_message';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'customfunction',
            'values' => array(
                'name'     => array( 'type' => 'select_name', 'label' => rex_i18n::msg("yform_validate_customfunction_name")),
                'function' => array( 'type' => 'text',  'label' => rex_i18n::msg("yform_validate_customfunction_function")),
                'params'   => array( 'type' => 'text',   'label' => rex_i18n::msg("yform_validate_customfunction_params")),
                'message'  => array( 'type' => 'text',   'label' => rex_i18n::msg("yform_validate_customfunction_message")),
            ),
            'description' => rex_i18n::msg("yform_validate_customfunction_description"),
            'multi_edit' => false,
        );

    }

}
