<?php

namespace Yakamara\YForm\Validate;

use Yakamara\YForm\Attribute\AsValidate;
use Redaxo\Core\Translation\I18n;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValidate('size')]
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
                'name' => ['type' => 'select_name', 'label' => I18n::msg('yform_validate_size_name')],
                'size' => ['type' => 'text', 'label' => I18n::msg('yform_validate_size_size')],
                'message' => ['type' => 'text', 'label' => I18n::msg('yform_validate_size_message')],
            ],
            'description' => I18n::msg('yform_validate_size_description'),
        ];
    }
}
