<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_preg_match extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        if ($this->params['send'] == '1') {
            $pm = $this->getElement(3);

            $Object = $this->getValueObject();

            preg_match($pm, $Object->getValue(), $matches);

            if (count($matches) > 0 && current($matches) == $Object->getValue()) {
            } else {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement(4);
            }
        }
    }

    public function getDescription()
    {
        return 'validate|preg_match|name|/[a-z]/i|warning_message ';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'validate',
            'name' => 'preg_match',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'pattern' => ['type' => 'text',    'label' => rex_i18n::msg('yform_validate_preg_match_pattern'), 'default' => '', 'notice' => rex_i18n::msg('yform_validate_preg_match_pattern_info')],
                'message' => ['type' => 'text',    'label' => rex_i18n::msg('yform_validate_defaults_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_preg_match_description'),
            'famous' => false,
        ];
    }
}
