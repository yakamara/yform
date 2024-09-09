<?php

namespace Yakamara\YForm\Value;

use rex_i18n;

class IP extends AbstractValue
{
    public function enterObject()
    {
        $sk = ('' != $this->getElement('server_var')) ? $this->getElement('server_var') : 'REMOTE_ADDR';
        $this->setValue($_SERVER[$sk]);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'ip|name|[no_db]|[server_var]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'ip',
            'values' => [
                'name' => ['type' => 'name',        'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',       'label' => rex_i18n::msg('yform_values_defaults_label')],
                'no_db' => ['type' => 'no_db',      'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'server_var' => ['type' => 'text',  'label' => rex_i18n::msg('yform_values_ip_server_var'), 'notice' => rex_i18n::msg('yform_values_ip_server_var_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_ip_description'),
            'db_type' => ['varchar(191)', 'text'],
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
