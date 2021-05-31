<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_emptyname extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $value = $this->getValue();
        if (!$value) {
            $value = '';
        }
        $this->setValue($value);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if ($this->needsOutput()) {
            if (1 == $this->getElement('show_value')) {
                $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php');
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
            }
        }
    }

    public function getDescription()
    {
        return 'emptyname|name|';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'emptyname',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'show_value' => ['type' => 'checkbox',  'label' => rex_i18n::msg('yform_values_defaults_showvalue'), 'default' => '0', 'options' => '0,1'],
            ],
            'description' => rex_i18n::msg('yform_values_emptyname_description'),
            'db_type' => ['text', 'mediumtext'],
            'multi_edit' => 'always',
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

        if ('(empty)' == $value) {
            return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';
        }
        if ('!(empty)' == $value) {
            return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';
        }

        $pos = strpos($value, '*');
        if (false !== $pos) {
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
