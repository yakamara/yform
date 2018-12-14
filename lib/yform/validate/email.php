<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_email extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        $Object = $this->getValueObject();

        if (!$this->isObject($Object)) {
            return;
        }

        if ($Object->getValue() == '') {
            return;
        }

        if ($Object->getValue()) {
            // https://html.spec.whatwg.org/multipage/forms.html#valid-e-mail-address
            if (!preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/", $Object->getValue())) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
            }
        }

    }

    public function getDescription()
    {
        return 'validate|email|emailname|warning_message ';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'validate',
            'name' => 'email',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_email_name')],
                'message' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_email_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_email_description'),
        ];
    }
}
