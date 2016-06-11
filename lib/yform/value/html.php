<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_html extends rex_yform_value_abstract
{

    function enterObject()
    {
        $this->params['form_output'][$this->getId()] = $this->getElement(2);
    }

    function getDescription()
    {
        return htmlspecialchars(stripslashes('html -> Beispiel: html|name|<div class="block"></div>'));
    }

    function getDefinitions()
    {

        return array(
            'type' => 'value',
            'name' => 'html',
            'values' => array(
                'name' => array( 'type' => 'name',   'label' => 'Feld' ),
                'html' => array( 'type' => 'textarea',    'label' => 'HTML'),
            ),
            'description' => rex_i18n::msg("yform_values_html_description"),
            'dbtype' => 'text'
        );

    }

}
