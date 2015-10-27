<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_compare_value extends rex_yform_validate_abstract
{

    function enterObject()
    {

        if ($this->params['send'] == '1') {

            $compare_type = $this->getElement('compare_type');
            $compare_value = $this->getElement('compare_value');

            $field_name = $this->getElement('name');
            $field_id = "";
            $field_value = "";
            foreach ($this->obj as $o) {
                if ($o->getName() == $field_name) {
                    $field_id    = $o->getId();
                    $field_value = $o->getValue();
                }
            }

            $error = false;
            switch($compare_type) {
                case("<="):
                    if ($field_value <= $compare_value) {
                        $error = true;
                    }
                    break;
                case(">="):
                    if ($field_value >= $compare_value) {
                        $error = true;
                    }
                    break;
                case(">"):
                    if ($field_value > $compare_value) {
                        $error = true;
                    }
                    break;
                case("<"):
                    if ($field_value < $compare_value) {
                        $error = true;
                    }
                    break;
                case("=="):
                    if ($field_value == $compare_value) {
                        $error = true;
                    }
                    break;
                case("!="):
                default:
                    if ($field_value != $compare_value) {
                        $error = true;
                    }
            }

            if ($error) {
                $this->params['warning'][$field_id] = $this->params['error_class'];
                $this->params['warning_messages'][$field_id] = $this->getElement('message');
            }

        }

    }

    function getDescription()
    {
        return 'compare_value -> compare label with value, example: validate|compare_value|label|value|[!=|<|>|==|>=|<=]|warning_message ';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'compare_value',
            'values' => array(
                'name'    => array( 'type' => 'select_name', 'label' => 'Feldname' ),
                'compare_value'   => array( 'type' => 'text', 'label' => 'Vergleichswert'),
                'compare_type' => array ('type' => 'select', 'label' => 'Vergleichsart', 'options' => '!\=,<,>,\=\=,>\=,<\=', 'default' => '!\='),
                'message' => array( 'type' => 'text',        'label' => 'Fehlermeldung'),

            ),
            'description' => 'Wert wird mit einem Feld verglichen',
        );

    }
}
