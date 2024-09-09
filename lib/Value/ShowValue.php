<?php

namespace Yakamara\YForm\Value;

use rex_i18n;

class ShowValue extends AbstractValue
{
    public function enterObject()
    {
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
    }

    public function getDescription(): string
    {
        return 'showvalue|name|label|defaultwert|notice';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'showvalue',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_text_default')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_showvalue_description'),
            'db_type' => ['text', 'varchar(191)'],
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
