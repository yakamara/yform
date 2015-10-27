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
        return 'emptyname -> Beispiel: emptyname|name|';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'emptyname',
            'values' => array(
                'name' => array( 'type' => 'name',   'label' => 'Feld' ),
                'label' => array( 'type' => 'text',    'label' => 'Bezeichnung'),
            ),
            'description' => 'Ein leeres Feld - unsichtbar im Formular',
            'dbtype' => 'text'
        );

    }
}
