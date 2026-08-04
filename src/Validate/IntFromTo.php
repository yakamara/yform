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

#[AsValidate('intfromto')]
class IntFromTo extends AbstractValidate
{
    public function enterObject()
    {
        $from = (int) $this->getElement('from');
        $to = (int) $this->getElement('to');

        $Object = $this->getValueObject();

        if (!$this->isObject($Object)) {
            return;
        }

        $value = $Object->getValue();
        $value_int = (int) $value;

        if ("$value" != "$value_int" || $value_int < $from || $value_int > $to) {
            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
        }
    }

    public function getDescription(): string
    {
        return 'validate|intfromto|name|from|to|warning_message';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'validate',
            'name' => 'intfromto',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => I18n::msg('yform_validate_intfromto_name')],
                'from' => ['type' => 'text',        'label' => I18n::msg('yform_validate_intfromto_from')],
                'to' => ['type' => 'text',        'label' => I18n::msg('yform_validate_intfromto_to')],
                'message' => ['type' => 'text',        'label' => I18n::msg('yform_validate_intfromto_message')],
            ],
            'description' => I18n::msg('yform_validate_intfromto_description'),
        ];
    }
}
