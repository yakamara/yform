<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('ip')]
class IP extends AbstractValue
{
    public function enterObject()
    {
        $sk = ('' != $this->getElement('server_var')) ? $this->getElement('server_var') : 'REMOTE_ADDR';
        // A misconfigured server_var — or the CLI, which has no REMOTE_ADDR — must not
        // push null into the value.
        $this->setValue($_SERVER[$sk] ?? '');

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
                'name' => ['type' => 'name',        'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',       'label' => I18n::msg('yform_values_defaults_label')],
                'no_db' => ['type' => 'no_db',      'label' => I18n::msg('yform_values_defaults_table'),  'default' => 0],
                'server_var' => ['type' => 'text',  'label' => I18n::msg('yform_values_ip_server_var'), 'notice' => I18n::msg('yform_values_ip_server_var_notice')],
            ],
            'description' => I18n::msg('yform_values_ip_description'),
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
