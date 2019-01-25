<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_compare extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        $compare_type = $this->getElement('compare_type');

        $field_1 = $this->getElement('name');
        $field_2 = $this->getElement('name2');

        foreach ($this->getObjects() as $o) {
            if ($o->getName() == $field_1) {
                $id_1 = !isset($id_1) ? $o->getId() : $id_1;
                $value_1 = !isset($value_1) ? $o->getValue() : $value_1;
            }
            if ($o->getName() == $field_2) {
                $id_2 = !isset($id_2) ? $o->getId() : $id_2;
                $value_2 = !isset($value_2) ? $o->getValue() : $value_2;
            }
        }

        $error = false;
        switch ($compare_type) {
            case '<=':
                if ($value_1 <= $value_2) {
                    $error = true;
                }
                break;
            case '>=':
                if ($value_1 >= $value_2) {
                    $error = true;
                }
                break;
            case '>':
                if ($value_1 > $value_2) {
                    $error = true;
                }
                break;
            case '<':
                if ($value_1 < $value_2) {
                    $error = true;
                }
                break;
            case '==':
                if ($value_1 == $value_2) {
                    $error = true;
                }
                break;
            case '!=':
            default:
                if ($value_1 != $value_2) {
                    $error = true;
                }
        }

        if ($error) {
            $this->params['warning'][$id_1] = $this->params['error_class'];
            $this->params['warning'][$id_2] = $this->params['error_class'];
            $this->params['warning_messages'][$id_1] = $this->getElement('message');
            // $this->params['warning_messages'][$id_2] = $this->getElement('message');
        }
    }

    public function getDescription()
    {
        return 'validate|compare|name1|name2|[!=/</>/==/>=/<=]|warning_message|';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'validate',
            'name' => 'compare',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_compare_firstname')],
                'name2' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_compare_secondname')],
                'compare_type' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_validate_compare_type'), 'choices' => '!\=,<,>,\=\=,>\=,<\=', 'default' => '!\='],
                'message' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_compare_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_compare_description'),
            'multi_edit' => false,
        ];
    }
}
