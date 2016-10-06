<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_size extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $Object = $this->getValueObject($this->getElement("name"));

            if ($Object->getValue() == '') {
                return;
            }

            if (strlen($Object->getValue()) != $this->getElement('size')) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');

            }

        }
    }

    function getDescription()
    {
        return 'validate|size|plz|6|warning_message';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'size',
            'values' => array(
                'name'    => array( 'type' => 'select_name', 'label' => rex_i18n::msg("yform_validate_size_name")),
                'size'    => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_validate_size_size")),
                'message' => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_validate_size_message")),
            ),
            'description' => rex_i18n::msg("yform_validate_size_description"),
        );

    }

}
