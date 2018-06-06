<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_emptyname extends rex_yform_value_abstract
{
    public function enterObject()
    {
    }

    public function getDescription()
    {
        return 'emptyname|name|';
    }

    public function getDefinitions($values = [])
    {
        return [
            'type' => 'value',
            'name' => 'emptyname',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
            ],
            'description' => rex_i18n::msg('yform_values_emptyname_description'),
            'dbtype' => 'text',
            'multi_edit' => 'always',
        ];
    }
}
