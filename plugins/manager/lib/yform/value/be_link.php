<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_be_link extends rex_yform_value_abstract
{

    function enterObject()
    {
        static $counter = 0;
        $counter++;

        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement(3));
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.be_link.tpl.php', compact('counter'));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(4) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }


    function getDescription()
    {
        return 'be_link -> Beispiel: be_link|name|label|defaultwert|no_db';
    }


    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'be_link',
            'values' => array(
                'name' => array( 'type' => 'name',   'label' => 'Name' ),
                'label' => array( 'type' => 'text',   'label' => 'Bezeichnung'),
            ),
            'description' => rex_i18n::msg("yform_values_be_link_description"),
            'dbtype' => 'text'
        );
    }


    static function getListValue($params)
    {
        if (intval($params['value']) < 1) {
            return '-';
        }

        if (($article = rex_article::get($params['value']))) {
            return $article->getValue('name');
        } else {
            return 'article ' . $params['value'] . ' not found';
        }
    }

}
