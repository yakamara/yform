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

        if (is_array($this->getElement('values'))) {
            $values = $this->getElement('values');
            $w = $values[0];
            $v = $values[1];
        } else if ($this->getElement('values') == '') {
            $w = 0;
            $v = 1;
        } else {
            $values = explode(',', $this->getElement('values'));
            if (count($values) == 1) {
                $w = '';
                $v = $values[0];
            } else {
                $w = $values[0];
                $v = $values[1];
            }
        }
        $values = [$w,$v];

        if ($this->params['send'] != 1 && $this->getElement('default') == 1 && !in_array($this->getValue() , $values) ) {
            $this->setValue($v);

        } elseif ($this->getValue() == $v) {
            $this->setValue($v);

        } else {
            $this->setValue($w);
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.checkbox.tpl.php', ['value' => $v]);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(5) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'checkbox|name|label|Values(0,1)|default clicked (0/1)|[no_db]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'checkbox',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'values' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_checkbox_values'), 'default' => '0,1'],
                'default' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_checkbox_default'), 'default' => 0, 'values' => '0,1'],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_checkbox_description'),
            'dbtype' => 'varchar(191)',
            'famous' => true,
        ];
    }

    public static function getSearchField($params)
    {
        if ($params['field']->getElement('values') == '') {
            $v = 1; // gecheckt
            $w = 0; // nicht gecheckt
        } else {
            $values = explode(',', $params['field']->getElement('values'));

            if (count($values) == 1) {
                $v = $values[0];
                $w = '';
            } else {
                $v = $values[1];
                $w = $values[0];
            }
        }

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
