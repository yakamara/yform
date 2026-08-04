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

#[AsValidate('preg_match')]
class PregMatch extends AbstractValidate
{
    public function enterObject()
    {
        $pm = $this->getElement(3);

        $Object = $this->getValueObject();

        if (!$this->isObject($Object)) {
            return;
        }

        preg_match($pm, $Object->getValue(), $matches);

        if (is_countable($matches) && count($matches) > 0 && current($matches) == $Object->getValue()) {
        } else {
            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$Object->getId()] = $this->getElement(4);
        }
    }

    public function getDescription(): string
    {
        return 'validate|preg_match|name|/[a-z]/i|warning_message ';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'validate',
            'name' => 'preg_match',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => I18n::msg('yform_values_defaults_name')],
                'pattern' => ['type' => 'text',    'label' => I18n::msg('yform_validate_preg_match_pattern'), 'default' => '', 'notice' => I18n::msg('yform_validate_preg_match_pattern_info')],
                'message' => ['type' => 'text',    'label' => I18n::msg('yform_validate_defaults_message')],
            ],
            'description' => I18n::msg('yform_validate_preg_match_description'),
            'famous' => false,
        ];
    }
}
