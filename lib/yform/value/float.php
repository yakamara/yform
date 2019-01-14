<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_float extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        $this->setValue(self::formatValue($this->getValue(), $this->getElement('scale')));

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.float.tpl.php', 'value.text.tpl.php']);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement('no_db') != 1) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'float|name|label|scale|defaultwert|[no_db]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'float',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'scale' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_float_scale')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_float_default')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_float_description'),
            'deprecated' => rex_i18n::msg('yform_values_deprecated_float'),
            'db_type' => ['varchar(191)'],
        ];
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel()]);
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();

        $value = $params['value'];
        $field = $sql->escapeIdentifier($params['field']->getName());

        if ($value == '(empty)') {
            return ' (' . $field . ' = "" or ' . $field . ' IS NULL) ';
        }
        if ($value == '!(empty)') {
            return ' (' . $field . ' <> "" and ' . $field . ' IS NOT NULL) ';
        }

        $scale = $params['field']->getElement('scale');
        $float = '-?\d*(?:[,.]\d+)?';
        if (preg_match('/^\s*(' . $float . ')\s*\.\.\s*(' . $float . ')\s*$/', $value, $match)) {
            $match[1] = self::formatValue($match[1], $scale);
            $match[2] = self::formatValue($match[2], $scale);
            return ' ' . $field . ' BETWEEN ' . $match[1] . ' AND ' . $match[2];
        }
        preg_match('/^\s*(<|<=|>|>=|<>|!=)?\s*(.*)$/', $value, $match);
        $comparator = $match[1] ?: '=';
        $value = self::formatValue($match[2], $scale);
        return ' ' . $field . ' ' . $comparator . ' ' . $value;
    }

    protected static function formatValue($value, $scale)
    {
        return number_format((float) strtr($value, ',', '.'), (int) $scale, '.', '');
    }

    public function isDeprecated()
    {
        return true;
    }

}
