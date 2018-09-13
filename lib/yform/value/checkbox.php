<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_checkbox extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if ($this->params['send'] == 1 && $this->getValue() != 1) {
            $this->setValue(0);
        } elseif ($this->getValue() != '') {
            $this->setValue(($this->getValue() != 1) ? '0' : '1');
        } else {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.checkbox.tpl.php', ['value' => $this->getValue()]);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(5) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'checkbox|name|label|default clicked (0/1)|[no_db]|[notice]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'checkbox',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'checkbox', 'label' => rex_i18n::msg('yform_values_checkbox_default'), 'default' => 0],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_checkbox_description'),
            'db_type' => ['tinyint(1)'],
            'famous' => true,
            'hooks' => [
                'preDefault' => function (rex_yform_manager_field $field) {
                    return ($field->getElement('default') == 1) ? '1' : '0';
                },
            ],
        ];
    }

    public static function getSearchField($params)
    {
        $v = 1;
        $w = 0;

        $options = [];
        $options[$v] = 'checked';
        $options[$w] = 'not checked';
        $options[''] = '---';

        $params['searchForm']->setValueField('choice', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'choices' => $options,
        ]);
    }

    public static function getSearchFilter($params)
    {
        $value = $params['value'];
        $field = $params['field']->getName();

        $sql = rex_sql::factory();

        return ' ' . $sql->escapeIdentifier($field) . ' =  ' . $sql->escape($value) . '';
    }
}
