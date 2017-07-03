<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_select extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $multiple = $this->getElement('multiple') == 1;
        $options = $this->getArrayFromString($this->getElement('options'));

        if ($multiple) {
            $size = (int) $this->getElement('size');
            if ($size < 2) {
                $size = count($options);
            }

            $values = $this->getValue();
            if (!is_array($values)) {
                $values = explode(',', $values);
            }

            $real_values = [];
            foreach ($values as $value) {
                if (array_key_exists($value, $options)) {
                    $real_values[] = $value;
                }
            }

            $this->setValue($real_values);
        } else {
            $size = 1;
            $default = null;
            if (array_key_exists((string) $this->getElement('default'), $options)) {
                $default = $this->getElement('default');
            }
            $value = (string) $this->getValue();

            if ($value == '' || !array_key_exists($value, $options)) {
                if ($default) {
                    $this->setValue([$default]);
                } else {
                    reset($options);
                    $this->setValue([key($options)]);
                }
            } else {
                $this->setValue([$value]);
            }
        }

        // ---------- rex_yform_set
        if (isset($this->params['rex_yform_set'][$this->getName()]) && !is_array($this->params['rex_yform_set'][$this->getName()])) {
            $value = $this->params['rex_yform_set'][$this->getName()];
            $values = [];
            if (array_key_exists($value, $options)) {
                $values[] = $value;
            }
            $this->setValue($values);
            $this->setElement('disabled', true);
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.select.tpl.php', compact('options', 'multiple', 'size'));
        }

        $this->setValue(implode(',', $this->getValue()));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName() . '_NAME'] = isset($options[$this->getValue()]) ? $options[$this->getValue()] : null;

        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'select|name|label|Frau=w,Herr=m|[no_db]|defaultwert|multiple=1|selectsize';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'select',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'options' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_select_options')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),          'default' => 0],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_select_default')],
                'multiple' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_select_multiple')],
                'size' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_select_size')],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_select_description'),
            'dbtype' => 'text',
            'famous' => true,
        ];
    }

    public static function getListValue($params)
    {
        $return = [];

        $new_select = new self();
        $values = $new_select->getArrayFromString($params['params']['field']['options']);

        foreach (explode(',', $params['value']) as $k) {
            if (isset($values[$k])) {
                $return[] = rex_i18n::translate($values[$k]);
            }
        }

        return implode('<br />', $return);
    }

    public static function getSearchField($params)
    {
        $options = [];
        $options['(empty)'] = '(empty)';
        $options['!(empty)'] = '!(empty)';

        $new_select = new self();
        $options += $new_select->getArrayFromString($params['field']['options']);

        $params['searchForm']->setValueField('select', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'options' => $options,
            'multiple' => 1,
            'size' => 5,
        ]
        );
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();

        $field = $params['field']->getName();
        $values = (array) $params['value'];

        $where = [];
        foreach ($values as $value) {
            switch ($value) {
                case '(empty)':
                    $where[] = ' ' . $sql->escapeIdentifier($field) . ' = ""';
                    break;
                case '!(empty)':
                    $where[] = ' ' . $sql->escapeIdentifier($field) . ' != ""';
                    break;
                default:
                    $where[] = ' ( FIND_IN_SET( ' . $sql->escape($value) . ', ' . $sql->escapeIdentifier($field) . ') )';
                    break;
            }
        }

        if (count($where) > 0) {
            return ' ( ' . implode(' or ', $where) . ' )';
        }
    }
}
