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
        $html = $this->getElement('html');
        $label = $this->getElement('label');

        // BC
        if ($html == "") {
            $html = $label;
        }

        $this->params['form_output'][$this->getId()] = $html;
    }

    function getDescription()
    {
        return htmlspecialchars('html -> Beispiel: html|label|<div class="block"></div>');
    }

    function getDefinitions()
    {

        return array(
            'type' => 'value',
            'name' => 'html',
            'values' => array(
                'name' => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label' => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label")),
                'html' => array( 'type' => 'textarea',    'label' => rex_i18n::msg("yform_values_html_HTML")),
            ),
            'description' => rex_i18n::msg("yform_values_html_description"),
            'dbtype' => 'text',
            'multi_edit' => 'always',
            'search' => false,
            'list_hidden' => true,
        );

    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', array('name' => $params['field']->getName(), 'label' => $params['field']->getLabel()));
    }

}
