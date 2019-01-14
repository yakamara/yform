<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_ip extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $sk = ($this->getElement('server_var') != '') ? $this->getElement('server_var') : 'REMOTE_ADDR';
        $this->setValue($_SERVER[$sk]);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        dump($this->getElement('no_db'));

        if ($this->getElement('no_db') != 1) {

            echo "saved";

            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'ip|name|[no_db]|[server_var]';
    }

    public function getDefinitions()
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

}
