<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_compare_value extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        $compare_type = $this->getElement('compare_type');
        $compare_value = $this->getElement('compare_value');

        $Object = $this->getValueObject($this->getElement('name'));

        if (!$this->isObject($Object)) {
            return;
        }

        $field_value = $Object->getValue();

        $error = false;
        switch ($compare_type) {
            case '<=':
                if ($field_value <= $compare_value) {
                    $error = true;
                }
                break;
            case '>=':
                if ($field_value >= $compare_value) {
                    $error = true;
                }
                break;
            case '>':
                if ($field_value > $compare_value) {
                    $error = true;
                }
                break;
            case '<':
                if ($field_value < $compare_value) {
                    $error = true;
                }
                break;
            case '==':
                if ($field_value == $compare_value) {
                    $error = true;
                }
                break;
            case '!=':
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

    public function getDescription()
    {
        return 'validate|compare_value|name|value|[!=/</>/==/>=/<=]|warning_message ';
    }

    public function getDefinitions($values = [])
    {
        return [
            'type' => 'validate',
            'name' => 'compare_value',
            'values' => [
                'name' => ['type' => 'select_name', 'label' => rex_i18n::msg('yform_validate_compare_value_name')],
                'compare_value' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_compare_value_compare_value')],
                'compare_type' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_validate_compare_value_compare_type'), 'choices' => ['!=','<','>','==','>=','<='], 'default' => '!='],
                'message' => ['type' => 'text',        'label' => rex_i18n::msg('yform_validate_compare_value_message')],
            ],
            'description' => rex_i18n::msg('yform_validate_compare_value_description'),
            'multi_edit' => false,
        ];
    }
}
