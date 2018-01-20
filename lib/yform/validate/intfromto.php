<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_intfromto extends rex_yform_validate_abstract
{
    public function enterObject()
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

    public function getDescription()
    {
        return 'validate|intfromto|name|from|to|warning_message';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'validate',
            'name' => 'intfromto',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_intfromto_name')],
                'from' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_intfromto_from')],
                'to' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_intfromto_to')],
                'message' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_intfromto_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_intfromto_description'),
        ];
    }
}
