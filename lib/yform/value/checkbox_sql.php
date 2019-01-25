<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_checkbox_sql extends rex_yform_value_abstract
{
    public static $getListValues = [];

    public function enterObject()
    {
        if (!is_array($this->getValue())) {
            $this->setValue(explode(',', $this->getValue()));
        }

        $values = $this->getValue();

        // ----- query
        $sql = $this->getElement('query');

        $options_sql = rex_sql::factory();
        $options_sql->setDebug($this->params['debug']);
        $options = [];

        try {
            foreach ($options_sql->getArray($sql) as $option) {
                $options[$option['id']] = $option['name'];
            }
        } catch (rex_sql_exception $e) {
            dump($e);
        }

        $proofed_values = [];
        $proofed_name_values = [];
        foreach ($values as $value) {
            if (array_key_exists($value, $options)) {
                $proofed_values[$value] = $value;
                $proofed_name_values[$value] = $options[$value];
            }
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.checkbox_group.tpl.php', compact('options'));
        }

        $this->setValue(implode(',', $proofed_values));

        $this->params['value_pool']['email'][$this->getName()] = implode(', ', $proofed_name_values);
        if ($this->getElement('no_db') != 1) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'checkbox_sql|name|label:|select id,name from table order by name|';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'checkbox_sql',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'query' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_checkbox_sql_query')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_checkbox_sql_description'),
            'db_type' => ['text'],
            'deprecated' => rex_i18n::msg('yform_values_deprecated_checkbox_sql'),
        ];
    }

    public static function getListValue($params)
    {
        $return = [];

        $db = rex_sql::factory();
        // $db->debugsql = 1;

        $query = $params['params']['field']['query'];
        $pos = mb_strrpos(mb_strtoupper($query), 'ORDER BY ');
        if ($pos !== false) {
            $query = mb_substr($query, 0, $pos);
        }

        $pos = mb_strrpos(mb_strtoupper($query), 'LIMIT ');
        if ($pos !== false) {
            $query = mb_substr($query, 0, $pos);
        }

        $multiple = (int) $params['params']['field']['multiple'];
        if ($multiple != 1) {
            $where = ' `id` = ' . $db->escape($params['value']) . ' ';
        } else {
            $where = ' FIND_IN_SET(`id`, ' . $db->escape($params['value']) . ')';
        }

        $pos = mb_strrpos(mb_strtoupper($query), 'WHERE ');
        if ($pos !== false) {
            $query = mb_substr($query, 0, $pos) . ' WHERE ' . $where . ' AND ' . mb_substr($query, $pos + mb_strlen('WHERE '));
        } else {
            $query .= ' WHERE ' . $where;
        }

        $db_array = $db->getArray($query);

        foreach ($db_array as $entry) {
            $return[] = $entry['name'];
        }

        if (count($return) == 0 && $params['value'] != '' && $params['value'] != '0') {
            $return[] = $params['value'];
        }

        return implode('<br />', $return);
    }

    public function isDeprecated()
    {
        return true;
    }
}
