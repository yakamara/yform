<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_emptyname extends rex_yform_value_abstract
{

    function enterObject()
    {

    }

    function getDescription()
    {
        return 'emptyname|name|';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'emptyname',
            'values' => array(
                'name' => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
            ),
            'description' => rex_i18n::msg("yform_values_emptyname_description"),
            'dbtype' => 'text',
            'multi_edit' => 'always',
        );

    }
}
