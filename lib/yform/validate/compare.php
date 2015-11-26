<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_compare extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $compare_type = $this->getElement('compare_type');

            $field_1 = $this->getElement('name');
            $field_2 = $this->getElement('name2');

            foreach ($this->getObjects() as $o) {
                if ($o->getName() == $field_1) {
                    $id_1    = !isset($id_1)    ? $o->getId()    : $id_1;
                    $value_1 = !isset($value_1) ? $o->getValue() : $value_1;
                }
                if ($o->getName() == $field_2) {
                    $id_2    = !isset($id_2)    ? $o->getId()    : $id_2;
                    $value_2 = !isset($value_2) ? $o->getValue() : $value_2;
                }
            }

            $error = false;
            switch($compare_type) {
                case("<="):
                    if ($value_1 <= $value_2) {
                        $error = true;
                    }
                    break;
                case(">="):
                    if ($value_1 >= $value_2) {
                        $error = true;
                    }
                    break;
                case(">"):
                    if ($value_1 > $value_2) {
                        $error = true;
                    }
                    break;
                case("<"):
                    if ($value_1 < $value_2) {
                        $error = true;
                    }
                    break;
                case("=="):
                    if ($value_1 == $value_2) {
                        $error = true;
                    }
                    break;
                case("!="):
                default:
                    if ($value_1 != $value_2) {
                        $error = true;
                    }
            }

            if ($error) {
                $this->params['warning'][$id_1] = $this->params['error_class'];
                $this->params['warning'][$id_2] = $this->params['error_class'];
                $this->params['warning_messages'][$id_1] = $this->getElement('message');
                $this->params['warning_messages'][$id_2] = $this->getElement('message');
            }

        }
    }

    function getDescription()
    {
        return 'compare -> prüft ob leer, beispiel: validate|compare|label1|label2|[!=|<|>|==|>=|<=]|warning_message|';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'compare',
            'values' => array(
                'name'    => array( 'type' => 'select_name', 'label' => '1. Feldname' ),
                'name2'   => array( 'type' => 'select_name', 'label' => '2. Feldname'),
                'compare_type' => array ('type' => 'select', 'label' => 'Vergleichsart', 'options' => '!\=,<,>,\=\=,>\=,<\=', 'default' => '!\='),
                'message' => array( 'type' => 'text',        'label' => 'Fehlermeldung'),

            ),
            'description' => '2 Felder werden miteinander verglichen',
        );

    }
}
