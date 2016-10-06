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

            $Object = $this->getValueObject($this->getElement('name'));
            $field_value = $Object->getValue();

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
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
            }

        }

    }

    function getDescription()
    {
        return 'validate|compare_value|label|value|[!=/</>/==/>=/<=]|warning_message ';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'compare_value',
            'values' => array(
                'name'    => array( 'type' => 'select_name', 'label' => rex_i18n::msg("yform_validate_compare_value_name")),
                'compare_value'   => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_validate_compare_value_compare_value")),
                'compare_type' => array ('type' => 'select', 'label' => rex_i18n::msg("yform_validate_compare_value_compare_type"), 'options' => '!\=,<,>,\=\=,>\=,<\=', 'default' => '!\='),
                'message' => array( 'type' => 'text',        'label' => rex_i18n::msg("yform_validate_compare_value_message")),

            ),
            'description' => rex_i18n::msg("yform_validate_compare_value_description"),
            'multi_edit' => false,
        );

    }
}
