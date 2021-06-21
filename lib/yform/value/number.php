<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_number extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ('' === $this->getValue()) {
            $this->setValue(null);
        } else {
            $this->setValue($this->getValue());
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.number-view.tpl.php', 'value.integer-view.tpl.php', 'value.view.tpl.php'], ['prepend' => $this->getElement('unit')]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.number.tpl.php', 'value.integer.tpl.php', 'value.text.tpl.php'], ['prepend' => $this->getElement('unit')]);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'number|name|label|precision|scale|defaultwert|[no_db]|[unit]|[notice]|[attributes]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'number',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'precision' => ['type' => 'integer', 'label' => rex_i18n::msg('yform_values_number_precision'), 'default' => '10'],
                'scale' => ['type' => 'integer', 'label' => rex_i18n::msg('yform_values_number_scale'), 'default' => '2'],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_number_default')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'unit' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_unit')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
            ],
            'validates' => [
                ['type' => ['name' => 'precision', 'type' => 'integer', 'message' => rex_i18n::msg('yform_values_number_error_precision', '1', '65'), 'not_required' => false]],
                ['type' => ['name' => 'scale', 'type' => 'integer', 'message' => rex_i18n::msg('yform_values_number_error_scale', '0', '30'), 'not_required' => false]],
                ['compare' => ['name' => 'scale', 'name2' => 'precision', 'message' => rex_i18n::msg('yform_values_number_error_compare'), 'compare_type' => '>']],
                ['intfromto' => ['name' => 'precision', 'from' => '1', 'to' => '65', 'message' => rex_i18n::msg('yform_values_number_error_precision', '1', '65')]],
                ['intfromto' => ['name' => 'scale', 'from' => '0', 'to' => '30', 'message' => rex_i18n::msg('yform_values_number_error_scale', '0', '30')]],
            ],
            'description' => rex_i18n::msg('yform_values_number_description'),
            'db_type' => ['DECIMAL({precision},{scale})'],
            'hooks' => [
                'preCreate' => static function (rex_yform_manager_field $field, $db_type) {
                    $db_type = str_replace('{precision}', $field->getElement('precision') ?? 6, $db_type);
                    $db_type = str_replace('{scale}', $field->getElement('scale') ?? 2, $db_type);
                    return $db_type;
                },
            ],
            'db_null' => true,
        ];
    }

    public static function getSearchField($params)
    {
        rex_yform_value_integer::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return rex_yform_value_integer::getSearchFilter($params);
    }

    public static function getListValue($params)
    {
        return rex_yform_value_integer::getListValue($params);
    }
}
