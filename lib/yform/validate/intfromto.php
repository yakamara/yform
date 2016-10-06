<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_intfromto extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $from = (int) $this->getElement('from');
            $to = (int) $this->getElement('to');

            $Object = $this->getValueObject();

            $value = $Object->getValue();
            $value_int = (int) $value;

            if ("$value" != "$value_int" || $value_int < $from || $value_int > $to) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
            }

        }
    }

    function getDescription()
    {
        return 'validate|intfromto|label|from|to|warning_message';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'intfromto',
            'values' => array(
                'name'    => array( 'type' => 'select_name', 'label' => rex_i18n::msg("yform_validate_intfromto_name")),
                'from'    => array( 'type' => 'text',        'label' => rex_i18n::msg("yform_validate_intfromto_from")),
                'to'      => array( 'type' => 'text',        'label' => rex_i18n::msg("yform_validate_intfromto_to")),
                'message' => array( 'type' => 'text',        'label' => rex_i18n::msg("yform_validate_intfromto_message")),
            ),
            'description' => rex_i18n::msg("yform_validate_intfromto_description"),
        );
    }


}
