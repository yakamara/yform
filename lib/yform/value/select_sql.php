<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_select_sql extends rex_yform_value_abstract
{
    public static $getListValues = [];

    public function enterObject()
    {
        $multiple = $this->getElement('multiple') == 1;

        $sql = $this->getElement('query');

        $options_sql = rex_sql::factory();
        $options_sql->setDebug($this->params['debug']);

        $options = [];

        try {
            foreach ($options_sql->getArray($sql) as $t) {
                $options[$t['id']] = $t['name'];
            }
        } catch (rex_sql_exception $e) {
            dump($e);
        }

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

            if ($this->getElement('empty_option') == 1) {
                $options = ['0' => (string) $this->getElement('empty_value')] + $options;
            }

            $default = null;
            if (array_key_exists((string) $this->getElement('default'), $options)) {
                $default = $this->getElement('default');
            }
            $value = (string) $this->getValue();

            if (!array_key_exists($value, $options)) {
                if ($default or $default === '0') {
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
        // ----------

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
        return 'select_sql|label|Bezeichnung:| select id,name from table order by name | [defaultvalue] | [no_db] |1/0 Leeroption|Leeroptionstext|1/0 Multiple Feld|selectsize';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'select_sql',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'query' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_select_sql_query')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_select_sql_default')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'empty_option' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_select_sql_empty_option')],
                'empty_value' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_select_sql_empty_value')],
                'multiple' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_select_sql_multiple')],
                'size' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_select_sql_size')],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_select_sql_description'),
            'dbtype' => 'text',
        ];
    }

    public static function getListValue($params)
    {
        $return = [];

        $query = $params['params']['field']['query'];
        $query_params = [];
        $pos = strrpos(strtoupper($query), 'ORDER BY ');
        if ($pos !== false) {
            $query = substr($query, 0, $pos);
        }

        $pos = strrpos(strtoupper($query), 'LIMIT ');
        if ($pos !== false) {
            $query = substr($query, 0, $pos);
        }

        $multiple = (isset($params['params']['field']['multiple'])) ? (int) $params['params']['field']['multiple'] : 0;
        if ($multiple != 1) {
            $where = ' `id` = ?';
            $query_params[] = $params['value'];
        } else {
            $where = ' FIND_IN_SET(`id`, ?)';
            $query_params[] = $params['value'];
        }

        $pos = strrpos(strtoupper($query), 'WHERE ');
        if ($pos !== false) {
            $query = substr($query, 0, $pos) . ' WHERE ' . $where . ' AND ' . substr($query, $pos + strlen('WHERE '));
        } else {
            $query .= ' WHERE ' . $where;
        }

        $db = rex_sql::factory();
        $db_array = $db->getArray($query, $query_params);

        foreach ($db_array as $entry) {
            $return[] = $entry['name'];
        }

        if (count($return) == 0 && $params['value'] != '' && $params['value'] != '0') {
            $return[] = $params['value'];
        }

        return implode('<br />', $return);
    }

    public static function getSearchField($params)
    {
        $options = [];
        $options['(empty)'] = '(empty)';
        $options['!(empty)'] = '!(empty)';

        $options_sql = rex_sql::factory();
        $options_sql->setQuery($params['field']['query']);

        foreach ($options_sql->getArray() as $t) {
            $options[$t['id']] = $t['name'];
        }

        $params['searchForm']->setValueField('select', [
                'name' => $params['field']->getName(),
                'label' => $params['field']->getLabel(),
                'options' => $options,
                'multiple' => 1,
                'size' => 5,
                'notice' => rex_i18n::msg('yform_search_defaults_select_notice'),
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
                    $where[] = $sql->escapeIdentifier($field).' = ""';
                    break;
                case '!(empty)':
                    $where[] = $sql->escapeIdentifier($field).' != ""';
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
