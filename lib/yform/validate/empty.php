<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_empty extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        if ($this->params['send'] == '1') {
            $Object = $this->getValueObject();

            if ($Object->getValue() == '') {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
            }
        }
    }

    public function getDescription()
    {
        return 'validate|empty|name|warning_message ';
    }

    public function getDefinitions($values = [])
    {
        return [
            'type' => 'validate',
            'name' => 'empty',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_empty_name')],
                'message' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_empty_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_empty_description'),
            'famous' => true,
        ];
    }
}
