<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_lang_textarea extends rex_yform_value_abstract
{

    static function getLangDivider()
    {
        return '^^^^°°°°';
    }

    function enterObject()
    {


        $text = array();
        if (is_array($this->getvalue())) {
            foreach ($this->getvalue() as $k => $t) {
                $text[$k] = $t;

            }

        } elseif (is_string($this->getvalue()) and $this->getvalue() != '') {
            $text = explode(self::getLangDivider(), $this->getValue());

        }

        foreach ($REX['CLANG'] as $l => $lang) {
            if (!isset($text[$l])) {
                $text[$l] = '';
            }
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.lang_textarea.tpl.php', array('text' => $text));

        $this->setValue(implode(self::getLangDivider(), $text));

        $this->params['value_pool']['email'][$this->getName()] = stripslashes($this->getValue());
        if ($this->getElement(3) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    function getDescription()
    {
        return 'textarea -> Beispiel: lang_textarea|name|label|[no_db]';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'lang_textarea',
            'values' => array(
                'name'  => array( 'type' => 'name',   'label' => 'Feld' ),
                'label' => array( 'type' => 'text',    'label' => 'Bezeichnung'),
                'no_db' => array( 'type' => 'no_db',   'label' => 'Datenbank',  'default' => 0),
            ),
            'description' => 'Ein mehrzeiliges mehrsprachiges Textfeld als Eingabe',
            'dbtype' => 'text'
        );
    }
}
