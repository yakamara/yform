<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_email extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $Object = $this->getValueObject();

            if ($Object->getValue() == "") {
                return;
            }

            if ($Object->getValue()) {
                // https://html.spec.whatwg.org/multipage/forms.html#valid-e-mail-address
                if (!filter_var($Object->getValue(), FILTER_VALIDATE_EMAIL)) {
                    $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                    $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
                }

                

            }

        }
    }

    function getDescription()
    {
        return 'validate|email|emaillabel|warning_message ';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'email',
            'values' => array(
                'name'    => array( 'type' => 'select_name', 'label' => rex_i18n::msg("yform_validate_email_name")),
                'message' => array( 'type' => 'text',        'label' => rex_i18n::msg("yform_validate_email_message")),
            ),
            'description' => rex_i18n::msg("yform_validate_email_description"),
        );

    }

}
