<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_text extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $this->setValue((string) $this->getValue());

        if ($this->getValue() == '' && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.text.tpl.php', ['prepend' => $this->getElement('prepend'), 'append' => $this->getElement('append')]);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'text|name|label|defaultwert|[no_db]|[attributes]|notice';
    }

    public function getDefinitions($values = [])
    {
        return [
            'type' => 'value',
            'name' => 'text',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_text_default')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'prepend' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_prepend')],
                'append' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_append')],
            ],
            'description' => rex_i18n::msg('yform_values_text_description'),
            'db_type' => ['varchar(191)', 'text'],
            'famous' => true,
            'hooks' => [
                'preDefault' => function (rex_yform_manager_field $field) {
                    return $field->getElement('default');
                },
            ],
        ];
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel(), 'notice' => rex_i18n::msg('yform_search_defaults_wildcard_notice')]);
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();
        $value = $params['value'];
        $field = $params['field']->getName();

        if ($value == '(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';
        }
        if ($value == '!(empty)') {
            return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';
        }

        $pos = strpos($value, '*');
        if ($pos !== false) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $sql->escapeIdentifier($field) . ' LIKE ' . $sql->escape($value);
        }
        return $sql->escapeIdentifier($field) . ' = ' . $sql->escape($value);
    }

    public static function getListValue($params)
    {
        $value = $params['subject'];
        $length = strlen($value);
        $title = $value;
        if ($length > 40) {
            $value = mb_substr($value, 0, 20).' ... '.mb_substr($value, -20);
        }
        return '<span title="'.rex_escape($title).'">'.rex_escape($value).'</span>';
    }
}
