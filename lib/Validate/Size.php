<?php

namespace Yakamara\YForm\Validate;

use rex_i18n;

class Size extends AbstractValidate
{
    public function enterObject()
    {
        $Object = $this->getValueObject($this->getElement('name'));

        if (!$this->isObject($Object)) {
            return;
        }

        if ('' == $Object->getValue()) {
            return;
        }

        if (mb_strlen($Object->getValue()) != $this->getElement('size')) {
            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
        }
    }

    public function getDescription(): string
    {
        return 'validate|size|name|size|warning_message';
    }

    public function getDefinitions(): array
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
