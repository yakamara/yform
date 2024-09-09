<?php

namespace Yakamara\YForm\Value;

use rex_i18n;

class EmptyName extends AbstractValue
{
    public function enterObject()
    {
        $value = $this->getValue();
        if (!$value) {
            $value = '';
        }
        $this->setValue($value);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if ($this->needsOutput()) {
            if (1 == $this->getElement('show_value')) {
                $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php');
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
            }
        }
    }

    public function getDescription(): string
    {
        return 'emptyname|name|';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'emptyname',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'show_value' => ['type' => 'checkbox',  'label' => rex_i18n::msg('yform_values_defaults_showvalue'), 'default' => '0', 'options' => '0,1'],
            ],
            'description' => rex_i18n::msg('yform_values_emptyname_description'),
            'db_type' => ['text', 'mediumtext'],
            'multi_edit' => 'always',
        ];
    }

    public static function getSearchField($params)
    {
        Text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return Text::getSearchFilter($params);
    }

    public static function getListValue($params)
    {
        return Text::getListValue($params);
    }
}
