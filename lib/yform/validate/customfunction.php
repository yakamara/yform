<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_customfunction extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        if ($this->params['send'] == '1') {
            $label = $this->getElement('name');
            $func = $this->getElement('function');
            $parameter = $this->getElement('params');

            $comparator = true;
            if (is_string($func) && mb_substr($func, 0, 1) == '!') {
                $comparator = false;
                $func = mb_substr($func, 1);
            }

            $Object = $this->getValueObject($label);

            if (!$this->isObject($Object)) {
                return;
            }

            if (!is_callable($func)) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = 'ERROR: customfunction "' . $func . '" not found';
            } elseif (call_user_func($func, $label, $Object->getValue(), $parameter, $this) === $comparator) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
            }
        }
    }

    public function getDescription()
    {
        return 'validate|customfunction|name|[!]function/class::method|weitere_parameter|warning_message';
    }

    public function getDefinitions($values = [])
    {
        return [
            'type' => 'validate',
            'name' => 'customfunction',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_customfunction_name')],
                'function' => ['type' => 'text',  'label' => rex_i18n::msg('yform_validate_customfunction_function')],
                'params' => ['type' => 'text',   'label' => rex_i18n::msg('yform_validate_customfunction_params')],
                'message' => ['type' => 'text',   'label' => rex_i18n::msg('yform_validate_customfunction_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_customfunction_description'),
            'multi_edit' => false,
        ];
    }
}
