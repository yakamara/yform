<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_size extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        $Object = $this->getValueObject($this->getElement('name'));

        if (!$this->isObject($Object)) {
            return;
        }

        if ($Object->getValue() == '') {
            return;
        }

        if (strlen($Object->getValue()) != $this->getElement('size')) {
            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
        }

    }

    public function getDescription()
    {
        return 'validate|size|name|size|warning_message';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'validate',
            'name' => 'size',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_size_name')],
                'size' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_size_size')],
                'message' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_size_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_size_description'),
        ];
    }
}
